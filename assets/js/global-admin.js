jQuery(document).ready(function () {
    function addCoolformAdmingPageToElementor() {
        let $elementorEditorPage = jQuery('.wp-submenu a[href="admin.php?page=elementor"]').closest('li');
        if (!$elementorEditorPage.length) {
            return;
        }

        let $submenu = $elementorEditorPage.closest('ul.wp-submenu');
        if (!$submenu.length) {
            return;
        }

        $submenu.find('.formsdb-page-list').remove();
        $submenu.find('.cfkef-entries-page-list').remove();

        let $formsdbItem = jQuery('<li class="formsdb-page-list"><a href="admin.php?page=formsdb">Formsdb</a></li>');
        let $coolFormEntriesItem = jQuery('<li class="cfkef-entries-page-list"><a href="admin.php?page=cfkef-entries">↳ Entries</a></li>');


        if($submenu.find('a[href="admin.php?page=elementor-one-upgrade"]').length > 0){
            $elementorEditorPage.after($coolFormEntriesItem)            
            $elementorEditorPage.after($formsdbItem)            
        }else{
            $submenu.append($formsdbItem);
            $submenu.append($coolFormEntriesItem);
        }

    }

    addCoolformAdmingPageToElementor();

    document.addEventListener('cfkef_dashboard_toggle:settings:changed', function (e) {
        addCoolformAdmingPageToElementor()
    });
});
