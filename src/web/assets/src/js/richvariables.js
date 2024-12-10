import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import DropdownButtonView from '@ckeditor/ckeditor5-ui/src/dropdown/button/dropdownbuttonview';
import DropdownView from '@ckeditor/ckeditor5-ui/src/dropdown/dropdownview';
import SplitButtonView from '@ckeditor/ckeditor5-ui/src/dropdown/button/splitbuttonview';
import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';
import { addListToDropdown, createDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import icon from '/img/RichVariables-menu-icon.svg?raw';

export default class RichVariables extends Plugin {
  init() {
    const editor = this.editor;

    editor.ui.componentFactory.add('richVariables', locale => {
      const dropdownView = createDropdown(locale);

      // Set up the button view
      dropdownView.buttonView.set({
        label: 'Variables',
        icon: icon,
        tooltip: true,
      });

      dropdownView.render();

      // Handle button click to fetch variables and populate the dropdown
      dropdownView.buttonView.on('open', () => {
        if (!dropdownView.isPopulated) {
          fetchVariables()
            .then(variables => {
              populateDropdown(dropdownView, variables, editor);
            })
            .catch(error => {
              console.error('Error fetching variables:', error);
            });
        }
      });

      return dropdownView;
    });
  }
}

/**
 * Populates the dropdown with the list of variables.
 */
function populateDropdown(dropdownView, variables, editor) {
  const items = new Collection();

  if (variables.length > 0) {
    variables.forEach(variable => {
      const itemModel = new Model({
        label: variable.title,
        withText: true,
      });

      itemModel.on('execute', () => {
        insertVariable(editor, variable.text);
      });

      items.add(itemModel);
    });
  } else {
    const itemModel = new Model({
      label: 'No Variables Found',
      withText: true,
    });

    items.add(itemModel);
  }

  addListToDropdown(dropdownView, items);
  dropdownView.isPopulated = true;
}

/**
 * Inserts the selected variable into the editor content.
 */
function insertVariable(editor, variableText) {
  editor.model.change(writer => {
    const insertionPosition = editor.model.document.selection.getFirstPosition();
    writer.insertText(variableText, insertionPosition);
  });
}

/**
 * Fetches the list of variables from the controller.
 */
function fetchVariables() {
  const cacheKey = 'rich-variables-menu-cache';
  const cachedData = getWithExpiry(cacheKey);

  if (cachedData) {
    return Promise.resolve(cachedData);
  } else {
    return fetch(Craft.getActionUrl('rich-variables/default/index'))
      .then(response => response.json())
      .then(data => {
        setWithExpiry(cacheKey, data.variablesList, 60 * 1000); // Cache for 1 minute
        return data.variablesList;
      });
  }
}

/**
 * Utility functions for caching with expiry.
 */
function setWithExpiry(key, value, ttl) {
  const now = new Date();
  const item = {
    value: value,
    expiry: now.getTime() + ttl,
  };
  localStorage.setItem(key, JSON.stringify(item));
}

function getWithExpiry(key) {
  const itemStr = localStorage.getItem(key);
  if (!itemStr) {
    return null;
  }
  const item = JSON.parse(itemStr);
  const now = new Date();
  if (now.getTime() > item.expiry) {
    localStorage.removeItem(key);
    return null;
  }
  return item.value;
}