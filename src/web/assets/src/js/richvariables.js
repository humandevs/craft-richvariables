
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

export default class RichVariables extends Plugin {
    init() {
        const editor = this.editor;

        editor.ui.componentFactory.add('richVariables', locale => {
            const view = new ButtonView(locale);

            view.set({
                label: 'Insert Variable',
                icon: this.getIcon(),
                tooltip: true,
            });

            // Handle the button click
            view.on('execute', () => {
                // Fetch variables via AJAX
                fetch('/actions/rich-variables/default/index')
                    .then(response => response.json())
                    .then(data => {
                        const variables = data.variablesList;

                        if (variables.length > 0) {
                            // Display a dropdown or dialog to select a variable
                            // For simplicity, insert the first variable
                            const variableText = variables[0].text;
                            editor.model.change(writer => {
                                editor.model.insertContent(writer.createText(variableText));
                            });
                        } else {
                            alert('No variables found');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching variables:', error);
                    });
            });

            return view;
        });
    }

    getIcon() {
        // Return the SVG icon content
        return '<svg><!-- SVG content of RichVariables-menu-icon.svg --></svg>';
    }
}