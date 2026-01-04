#!/usr/bin/env python3
"""Add CompanyContext to controllers"""

import re

# Controllers that need CompanyContext added to existing constructor
extend_constructors = [
    'ProjectDetailController',
    'ContributorSatisfactionController',
    'BadgeController',
    'ContributorSkillController',
    'LeadMagnetController'
]

# Controllers that need a new constructor created
create_constructors = [
    'AdminUserController',
    'SubscriptionController',
    'ProjectTechnologyController',
    'GdprController'
]

def add_to_existing_constructor(filepath):
    """Add CompanyContext parameter to existing constructor"""
    with open(filepath, 'r') as f:
        content = f.read()

    # Find constructor and add CompanyContext before closing
    # Pattern: match constructor with trailing comma, add new parameter
    pattern = r'(public function __construct\([^)]+)(,\s*\n\s*)\)(\s*\{)'
    replacement = r'\1\2    private readonly CompanyContext $companyContext\n    )\3'

    new_content = re.sub(pattern, replacement, content)

    if new_content != content:
        with open(filepath, 'w') as f:
            f.write(new_content)
        return True
    return False

def create_new_constructor(filepath):
    """Create new constructor after class declaration"""
    with open(filepath, 'r') as f:
        content = f.read()

    # Find class declaration and add constructor after the opening brace
    pattern = r'(class \w+Controller extends AbstractController\s*\{)\s*\n'
    replacement = r'\1\n    public function __construct(\n        private readonly CompanyContext $companyContext\n    ) {\n    }\n\n'

    new_content = re.sub(pattern, replacement, content)

    if new_content != content:
        with open(filepath, 'w') as f:
            f.write(new_content)
        return True
    return False

# Process controllers
print("Extending existing constructors...")
for ctrl in extend_constructors:
    filepath = f'src/Controller/{ctrl}.php'
    if add_to_existing_constructor(filepath):
        print(f"  ✓ {ctrl}")
    else:
        print(f"  ✗ {ctrl} - pattern not matched")

print("\nCreating new constructors...")
for ctrl in create_constructors:
    filepath = f'src/Controller/{ctrl}.php'
    if create_new_constructor(filepath):
        print(f"  ✓ {ctrl}")
    else:
        print(f"  ✗ {ctrl} - pattern not matched")

print("\nDone!")
