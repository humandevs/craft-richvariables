
(function (CKEDITOR) {
    CKEDITOR.plugins.add('richvariables', {
        icons: 'richvariables',
        init: function (editor) {
            editor.ui.addButton('RichVariables', {
                label: 'Insert Variable',
                command: 'insertVariable',
                toolbar: 'insert',
                icon: this.path + 'icons/richvariables.png',
            });

            editor.addCommand('insertVariable', {
                exec: function (editor) {
                    // Your code to open a dialog and insert a variable
                }
            });
        }
    });
})(window.CKEDITOR);