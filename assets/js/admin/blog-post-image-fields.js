/**
 * Conditional display of blog post image fields based on imageSource selection.
 *
 * Shows/hides:
 * - External URL field when imageSource = 'external'
 * - AI prompt field when imageSource = 'ai_generated'
 * - Upload field when imageSource = 'upload' (not yet implemented)
 */

document.addEventListener('DOMContentLoaded', function() {
    const imageSourceField = document.querySelector('select[name="BlogPost[imageSource]"]');

    if (!imageSourceField) {
        return; // Not on the blog post form
    }

    // Get all image-related fields
    const externalUrlField = document.querySelector('[data-image-field="external"]')?.closest('.form-group');
    const aiPromptField = document.querySelector('[data-image-field="ai_generated"]')?.closest('.form-group');

    /**
     * Show/hide fields based on selected image source
     */
    function updateImageFields() {
        const selectedSource = imageSourceField.value;

        // Hide all fields first
        if (externalUrlField) externalUrlField.style.display = 'none';
        if (aiPromptField) aiPromptField.style.display = 'none';

        // Show the appropriate field
        switch (selectedSource) {
            case 'external':
                if (externalUrlField) externalUrlField.style.display = 'block';
                break;
            case 'ai_generated':
                if (aiPromptField) aiPromptField.style.display = 'block';
                break;
            case 'upload':
                // Upload field handling will be added when ImageField is implemented
                break;
        }
    }

    // Initial state
    updateImageFields();

    // Listen for changes
    imageSourceField.addEventListener('change', updateImageFields);
});
