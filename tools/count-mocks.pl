#!/usr/bin/env perl
# count-mocks.pl — Audit createMock vs createStub usage in PHPUnit test files.
#
# Purpose: identify mocks that can be safely converted to createStub (no
#   ->expects() or ->with() called on them).
#
# Usage:
#   perl tools/count-mocks.pl [--json] tests/Unit/Service/FooTest.php [more...]
#   perl tools/count-mocks.pl --json $(find tests/Unit -name '*Test.php')
#
# Output (default text):
#   FILE: <path>
#     mock <var> @<line> -> <class> [convertible|review-needed|safe-stub]
#     ...
#   summary: <total> mocks, <convertible> can be createStub
#
# Output (--json):
#   {
#     "file": "<path>",
#     "mocks": [
#       {
#         "var": "$this->repository",
#         "class": "VacationRepositoryInterface",
#         "line": 43,
#         "has_expects": false,
#         "has_with": false,
#         "convertible": true
#       }
#     ],
#     "summary": {
#       "total": 4,
#       "convertible": 3,
#       "requires_review": 1
#     }
#   }
#
# Exit code: 0 always (informational tool, never fails the build).

use strict;
use warnings;
use File::Basename;

my $json_output = 0;
my @files;

for my $arg (@ARGV) {
    if ($arg eq '--json') {
        $json_output = 1;
    } elsif ($arg eq '--help' || $arg eq '-h') {
        print_help();
        exit 0;
    } else {
        push @files, $arg;
    }
}

if (!@files) {
    die "Usage: perl $0 [--json] <file1> [file2 ...]\n";
}

my @results;
for my $file (@files) {
    push @results, analyze_file($file);
}

if ($json_output) {
    print_json(\@results);
} else {
    print_text(\@results);
}

exit 0;

# ----- subroutines -----

sub analyze_file {
    my ($file) = @_;
    open(my $fh, '<', $file) or do {
        warn "WARN: cannot read $file: $!";
        return { file => $file, error => "$!" };
    };
    my @lines = <$fh>;
    close($fh);
    my $content = join('', @lines);

    my @mocks;

    # Pattern 1: assignment to property/var: $name = $this->createMock(Class::class)
    while ($content =~ /(\$\w+(?:->\w+)?)\s*=\s*\$this->createMock\(\s*([\w\\]+)::class\s*\)/g) {
        my $var = $1;
        my $class = $2;
        my $line = compute_line_number($content, pos($content));
        push @mocks, {
            var => $var,
            class => $class,
            line => $line,
            kind => 'assigned',
        };
    }

    # Pattern 2: inline (arg-passed) mocks: $this->createMock(X::class) NOT preceded by `=`
    my $line_no = 0;
    for my $ln (@lines) {
        $line_no++;
        next if $ln =~ /=/;  # assigned mocks already captured above
        while ($ln =~ /\$this->createMock\(\s*([\w\\]+)::class\s*\)/g) {
            push @mocks, {
                var => undef,
                class => $1,
                line => $line_no,
                kind => 'inline',
            };
        }
    }

    # For each assigned mock, check expects/with usage
    for my $mock (@mocks) {
        if ($mock->{kind} eq 'inline') {
            $mock->{has_expects} = 0;
            $mock->{has_with} = 0;
            $mock->{convertible} = 1;
            next;
        }
        my $escaped = quotemeta($mock->{var});
        $mock->{has_expects} = ($content =~ /$escaped\s*->expects\(/) ? 1 : 0;
        $mock->{has_with}    = ($content =~ /$escaped\s*->with\(/)    ? 1 : 0;
        $mock->{convertible} = (!$mock->{has_expects} && !$mock->{has_with}) ? 1 : 0;
    }

    # Property declaration analysis (helps detect FQN/intersection types)
    for my $mock (@mocks) {
        next unless defined $mock->{var};
        next unless $mock->{var} =~ /^\$this->(\w+)/;
        my $prop = $1;
        if ($content =~ /private\s+\S+&MockObject\s+\$$prop\b/) {
            $mock->{property_type} = 'intersection';
        } elsif ($content =~ /private\s+(?:\\?[\w\\]*?)?\bMockObject\s+\$$prop\b/) {
            $mock->{property_type} = 'plain-mockobject';
            $mock->{convertible} = 0;
            $mock->{requires_review} = 'plain MockObject property type would break on stub';
        } else {
            $mock->{property_type} = 'unknown';
        }
    }

    my $convertible = grep { $_->{convertible} } @mocks;
    my $requires_review = scalar(@mocks) - $convertible;

    return {
        file => $file,
        mocks => \@mocks,
        summary => {
            total => scalar(@mocks),
            convertible => $convertible,
            requires_review => $requires_review,
        },
    };
}

sub compute_line_number {
    my ($content, $pos) = @_;
    my $prefix = substr($content, 0, $pos);
    my $count = ($prefix =~ tr/\n//);
    return $count + 1;
}

sub print_text {
    my ($results) = @_;
    my $grand_total = 0;
    my $grand_convertible = 0;

    for my $r (@$results) {
        if ($r->{error}) {
            print "FILE: $r->{file} (ERROR: $r->{error})\n";
            next;
        }
        my $sum = $r->{summary};
        next if $sum->{total} == 0;
        print "FILE: $r->{file} ($sum->{total} mocks, $sum->{convertible} convertible)\n";
        for my $m (@{$r->{mocks}}) {
            my $tag = $m->{convertible} ? 'CONVERTIBLE' : 'REVIEW';
            my $var = defined $m->{var} ? $m->{var} : '(inline)';
            my $extra = '';
            $extra .= " [expects]" if $m->{has_expects};
            $extra .= " [with]"    if $m->{has_with};
            $extra .= " [type=$m->{property_type}]" if $m->{property_type};
            $extra .= " review-reason=\"$m->{requires_review}\"" if $m->{requires_review};
            printf "  %s @%d -> %s :: %s%s\n", $var, $m->{line}, $m->{class}, $tag, $extra;
        }
        $grand_total       += $sum->{total};
        $grand_convertible += $sum->{convertible};
    }

    printf "\nSUMMARY: %d files, %d mocks total, %d convertible (%d%% safe-to-convert)\n",
        scalar(@$results),
        $grand_total,
        $grand_convertible,
        $grand_total > 0 ? int(100 * $grand_convertible / $grand_total) : 0;
}

sub print_json {
    my ($results) = @_;
    print "[";
    my $first = 1;
    for my $r (@$results) {
        print "," unless $first;
        $first = 0;
        print "\n  ";
        print json_encode($r);
    }
    print "\n]\n";
}

sub json_encode {
    my ($data) = @_;
    if (!defined $data) {
        return 'null';
    } elsif (ref($data) eq 'HASH') {
        my @pairs;
        for my $k (sort keys %$data) {
            push @pairs, json_encode_key($k) . ':' . json_encode($data->{$k});
        }
        return '{' . join(',', @pairs) . '}';
    } elsif (ref($data) eq 'ARRAY') {
        return '[' . join(',', map { json_encode($_) } @$data) . ']';
    } elsif ($data =~ /^-?\d+(\.\d+)?$/) {
        return $data;
    } else {
        return json_encode_key($data);
    }
}

sub json_encode_key {
    my ($s) = @_;
    return 'null' unless defined $s;
    $s =~ s/\\/\\\\/g;
    $s =~ s/"/\\"/g;
    $s =~ s/\n/\\n/g;
    $s =~ s/\r/\\r/g;
    $s =~ s/\t/\\t/g;
    return '"' . $s . '"';
}

sub print_help {
    print <<'HELP';
count-mocks.pl — Audit createMock vs createStub usage in PHPUnit tests.

USAGE:
    perl tools/count-mocks.pl [--json] <file...>

OPTIONS:
    --json    Output JSON (one object per file in an array)
    --help    Show this help

EXAMPLES:
    # Audit a single file (text output)
    perl tools/count-mocks.pl tests/Unit/Service/FooTest.php

    # Audit all unit tests, JSON output, pipe to jq
    perl tools/count-mocks.pl --json $(find tests/Unit -name '*Test.php') | jq

    # Find convertible mocks across the codebase
    perl tools/count-mocks.pl --json $(find tests/Unit -name '*Test.php') \
      | jq '.[] | select(.summary.convertible > 0) | .file'

CONVENTIONS:
    A mock is "convertible" to createStub when:
      - it is never used with ->expects(...)
      - it is never used with ->with(...)
      - if assigned to a $this->property: the property type is NOT a plain
        MockObject (which would break the type contract on stub assignment)

EXIT CODE:
    Always 0. Tool is informational; failures only emit warnings.

REFERENCES:
    - sprint-007 TEST-MOCKS-003 (PR #123) — pattern origin
    - sprint-008 TEST-MOCKS-004 — primary consumer of this script
    - CONTRIBUTING.md — when to use createStub vs createMock
HELP
}
