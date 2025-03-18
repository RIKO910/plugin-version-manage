jQuery(document).ready(function ($) {
    // Handle adding new version inputs
    $('.add_version').on('click', function () {
        let versionIndex = $('.version_item').length + 1;

        let newVersionHTML = `
            <div class="version_item">
                <input type="text" name="version_name[]" placeholder="Version Name">
                <input type="date" name="release_date[]" placeholder="Release Date">
                <button type="button" class="remove_version">Remove</button>
            </div>
        `;

        $('#version_container').append(newVersionHTML);
    });

    // Handle removing version inputs
    $(document).on('click', '.remove_version', function () {
        $(this).closest('.version_item').remove();
    });
});
