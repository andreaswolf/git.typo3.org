/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

define('TYPO3/CMS/Rtehtmlarea/Component/Plugin', function() {

/**
 * HTMLArea.plugin class
 *
 * Every plugin should be a subclass of this class
 *
 */
var Plugin = function (editor, pluginName) {
};
Plugin = Ext.extend(Plugin, {
	/**
	 * HTMLArea.Plugin constructor
	 *
	 * @param	object		editor: instance of RTE
	 * @param	string		pluginName: name of the plugin
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	constructor: function (editor, pluginName) {
		this.editor = editor;
		this.editorNumber = editor.editorId;
		this.editorId = editor.editorId;
		this.editorConfiguration = editor.config;
		this.name = pluginName;
		try {
			this.I18N = HTMLArea.I18N[this.name];
		} catch(e) {
			this.I18N = new Object();
		}
		this.configurePlugin(editor);
	},
	/**
	 * Configures the plugin
	 * This function is invoked by the class constructor.
	 * This function should be redefined by the plugin subclass. Normal steps would be:
	 *	- registering plugin ingormation with method registerPluginInformation;
	 *	- registering any buttons with method registerButton;
	 *	- registering any drop-down lists with method registerDropDown.
	 *
	 * @param	object		editor: instance of RTE
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	configurePlugin: function (editor) {
		return false;
	},
	/**
	 * Registers the plugin "About" information
	 *
	 * @param	object		pluginInformation:
	 *					version		: the version,
	 *					developer	: the name of the developer,
	 *					developerUrl	: the url of the developer,
	 *					copyrightOwner	: the name of the copyright owner,
	 *					sponsor		: the name of the sponsor,
	 *					sponsorUrl	: the url of the sponsor,
	 *					license		: the type of license (should be "GPL")
	 *
	 * @return	boolean		true if the information was registered
	 */
	registerPluginInformation: function (pluginInformation) {
		if (typeof(pluginInformation) !== 'object') {
			this.appendToLog('registerPluginInformation', 'Plugin information was not provided', 'warn');
			return false;
		} else {
			this.pluginInformation = pluginInformation;
			this.pluginInformation.name = this.name;
			return true;
		}
	},

	/**
	 * Returns the plugin information
	 *
	 * @return	object		the plugin information object
	 */
	getPluginInformation: function () {
		return this.pluginInformation;
	},

	/**
	 * Returns a plugin object
	 *
	 * @param	string		pluinName: the name of some plugin
	 * @return	object		the plugin object or null
	 */
	getPluginInstance: function (pluginName) {
		return this.editor.getPlugin(pluginName);
	},

	/**
	 * Returns a current editor mode
	 *
	 * @return	string		editor mode
	 */
	getEditorMode: function () {
		return this.editor.getMode();
	},

	/**
	 * Returns true if the button is enabled in the toolbar configuration
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	boolean		true if the button is enabled in the toolbar configuration
	 */
	isButtonInToolbar: function (buttonId) {
		var index = -1;
		Ext.each(this.editorConfiguration.toolbar, function (row) {
			Ext.each(row, function (group) {
				index = group.indexOf(buttonId);
				return index === -1;
			});
			return index === -1;
		});
		return index !== -1;
	},

	/**
	 * Returns the button object from the toolbar
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	object		the toolbar button object
	 */
	getButton: function (buttonId) {
		return this.editor.toolbar.getButton(buttonId);
	},
	/**
	 * Registers a button for inclusion in the toolbar
	 *
	 * @param	object		buttonConfiguration: the configuration object of the button:
	 *					id		: unique id for the button
	 *					tooltip		: tooltip for the button
	 *					textMode	: enable in text mode
	 *					action		: name of the function invoked when the button is pressed
	 *					context		: will be disabled if not inside one of listed elements
	 *					hide		: hide in menu and show only in context menu (deprecated, use hidden)
	 *					hidden		: synonym of hide
	 *					selection	: will be disabled if there is no selection
	 *					hotkey		: hotkey character
	 *					dialog		: if true, the button opens a dialogue
	 *					dimensions	: the opening dimensions object of the dialogue window
	 *
	 * @return	boolean		true if the button was successfully registered
	 */
	registerButton: function (buttonConfiguration) {
		if (this.isButtonInToolbar(buttonConfiguration.id)) {
			if (Ext.isString(buttonConfiguration.action) && Ext.isFunction(this[buttonConfiguration.action])) {
				buttonConfiguration.plugins = this;
				if (buttonConfiguration.dialog) {
					if (!buttonConfiguration.dimensions) {
						buttonConfiguration.dimensions = { width: 250, height: 250};
					}
					buttonConfiguration.dimensions.top = buttonConfiguration.dimensions.top ?  buttonConfiguration.dimensions.top : this.editorConfiguration.dialogueWindows.defaultPositionFromTop;
					buttonConfiguration.dimensions.left = buttonConfiguration.dimensions.left ?  buttonConfiguration.dimensions.left : this.editorConfiguration.dialogueWindows.defaultPositionFromLeft;
				}
				buttonConfiguration.hidden = buttonConfiguration.hide;
					// Apply additional ExtJS config properties set in Page TSConfig
					// May not always work for values that must be integers
				if (this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]]) {
					Ext.applyIf(buttonConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]]);
				}
				if (this.editorConfiguration.registerButton(buttonConfiguration)) {
					var hotKey = buttonConfiguration.hotKey ? buttonConfiguration.hotKey :
						((this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]] && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey) ? this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey : null);
					if (!hotKey && buttonConfiguration.hotKey == "0") {
						hotKey = "0";
					}
					if (!hotKey && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]] && this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonConfiguration.id]].hotKey == "0") {
						hotKey = "0";
					}
					if (hotKey || hotKey == "0") {
						var hotKeyConfiguration = {
							id	: hotKey,
							cmd	: buttonConfiguration.id
						};
						return this.registerHotKey(hotKeyConfiguration);
					}
					return true;
				}
			} else {
				this.appendToLog('registerButton', 'Function ' + buttonConfiguration.action + ' was not defined when registering button ' + buttonConfiguration.id, 'error');
			}
		}
		return false;
	},
	/**
	 * Registers a drop-down list for inclusion in the toolbar
	 *
	 * @param	object		dropDownConfiguration: the configuration object of the drop-down:
	 *					id		: unique id for the drop-down
	 *					tooltip		: tooltip for the drop-down
	 *					action		: name of function to invoke when an option is selected
	 *					textMode	: enable in text mode
	 *
	 * @return	boolean		true if the drop-down list was successfully registered
	 */
	registerDropDown: function (dropDownConfiguration) {
		if (this.isButtonInToolbar(dropDownConfiguration.id)) {
			if (Ext.isString(dropDownConfiguration.action) && Ext.isFunction(this[dropDownConfiguration.action])) {
				dropDownConfiguration.plugins = this;
				dropDownConfiguration.hidden = dropDownConfiguration.hide;
				dropDownConfiguration.xtype = 'htmlareacombo';
					// Apply additional ExtJS config properties set in Page TSConfig
					// May not always work for values that must be integers
				if (this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[dropDownConfiguration.id]]) {
					Ext.applyIf(dropDownConfiguration, this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[dropDownConfiguration.id]]);
				}
				return this.editorConfiguration.registerButton(dropDownConfiguration);
			} else {
				this.appendToLog('registerDropDown', 'Function ' + dropDownConfiguration.action + ' was not defined when registering drop-down ' + dropDownConfiguration.id, 'error');
			}
		}
		return false;
	},
	/**
	 * Registers a text element for inclusion in the toolbar
	 *
	 * @param	object		textConfiguration: the configuration object of the text element:
	 *					id		: unique id for the text item
	 *					text		: the text litteral
	 *					tooltip		: tooltip for the text item
	 *					cls		: a css class to be assigned to the text element
	 *
	 * @return	boolean		true if the drop-down list was successfully registered
	 */
	registerText: function (textConfiguration) {
		if (this.isButtonInToolbar(textConfiguration.id)) {
			textConfiguration.plugins = this;
			textConfiguration.xtype = 'htmlareatoolbartext';
			return this.editorConfiguration.registerButton(textConfiguration);
		}
		return false;
	},

	/**
	 * Returns the drop-down configuration
	 *
	 * @param	string		dropDownId: the unique id of the drop-down
	 *
	 * @return	object		the drop-down configuration object
	 */
	getDropDownConfiguration : function(dropDownId) {
		return this.editorConfiguration.buttonsConfig[dropDownId];
	},

	/**
	 * Registors a hotkey
	 *
	 * @param	object		hotKeyConfiguration: the configuration object of the hotkey:
	 *					id		: the key
	 *					cmd		: name of the button corresponding to the hot key
	 *					element		: value of the record to be selected in the dropDown item
	 *
	 * @return	boolean		true if the hotkey was successfully registered
	 */
	registerHotKey : function (hotKeyConfiguration) {
		return this.editorConfiguration.registerHotKey(hotKeyConfiguration);
	},

	/**
	 * Returns the buttonId corresponding to the hotkey, if any
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	string		the buttonId or ""
	 */
	translateHotKey : function(key) {
		if (typeof(this.editorConfiguration.hotKeyList[key]) !== "undefined") {
			var buttonId = this.editorConfiguration.hotKeyList[key].cmd;
			if (typeof(buttonId) !== "undefined") {
				return buttonId;
			} else {
				return "";
			}
		}
		return "";
	},

	/**
	 * Returns the hotkey configuration
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	object		the hotkey configuration object
	 */
	getHotKeyConfiguration: function(key) {
		if (Ext.isDefined(this.editorConfiguration.hotKeyList[key])) {
			return this.editorConfiguration.hotKeyList[key];
		} else {
			return null;
		}
	},
	/**
	 * Initializes the plugin
	 * Is invoked when the toolbar component is created (subclass of Ext.ux.HTMLAreaButton or Ext.ux.form.HTMLAreaCombo)
	 *
	 * @param	object		button: the component
	 *
	 * @return	void
	 */
	init: Ext.emptyFn,
	/**
	 * The toolbar refresh handler of the plugin
	 * This function may be defined by the plugin subclass.
	 * If defined, the function will be invoked whenever the toolbar state is refreshed.
	 *
	 * @return	boolean
	 */
	onUpdateToolbar: Ext.emptyFn,
	/**
	 * The onMode event handler
	 * This function may be redefined by the plugin subclass.
	 * The function is invoked whenever the editor changes mode.
	 *
	 * @param	string		mode: "wysiwyg" or "textmode"
	 *
	 * @return	boolean
	 */
	onMode: function(mode) {
		if (mode === "textmode" && this.dialog && !(this.dialog.buttonId && this.editorConfiguration.buttons[this.dialog.buttonId] && this.editorConfiguration.buttons[this.dialog.buttonId].textMode)) {
			this.dialog.close();
		}
	},
	/**
	 * The onGenerate event handler
	 * This function may be defined by the plugin subclass.
	 * The function is invoked when the editor is initialized
	 *
	 * @return	boolean
	 */
	onGenerate: Ext.emptyFn,
	/**
	 * Localize a string
	 *
	 * @param	string		label: the name of the label to localize
	 *
	 * @return	string		the localization of the label
	 */
	localize: function (label, plural) {
		var i = plural || 0;
		var localized = this.I18N[label];
		if (typeof localized === 'object' && typeof localized[i] !== 'undefined') {
			localized = localized[i]['target'];
		} else {
			localized = HTMLArea.localize(label, plural);
		}
		return localized;
	},
	/**
	 * Get localized label wrapped with contextual help markup when available
	 *
	 * @param	string		fieldName: the name of the field in the CSH file
	 * @param	string		label: the name of the label to localize
	 * @param	string		pluginName: overrides this.name
	 *
	 * @return	string		localized label with CSH markup
	 */
	getHelpTip: function (fieldName, label, pluginName) {
		if (Ext.isDefined(TYPO3.ContextHelp)) {
			var pluginName = Ext.isDefined(pluginName) ? pluginName : this.name;
			if (!Ext.isEmpty(fieldName)) {
				fieldName = fieldName.replace(/-|\s/gi, '_');
			}
			return '<span class="t3-help-link" href="#" data-table="xEXT_rtehtmlarea_' + pluginName + '" data-field="' + fieldName + '"><abbr class="t3-help-teaser">' + (this.localize(label) || label) + '</abbr></span>';
		} else {
			return this.localize(label) || label;
		}
	},
	/**
	 * Load a Javascript file asynchronously
	 *
	 * @param	string		url: url of the file to load
	 * @param	function	callBack: the callBack function
	 *
	 * @return	boolean		true on success of the request submission
	 */
	getJavascriptFile: function (url, callback) {
		return this.editor.ajax.getJavascriptFile(url, callback, this);
	},
	/**
	 * Post data to the server
	 *
	 * @param	string		url: url to post data to
	 * @param	object		data: data to be posted
	 * @param	function	callback: function that will handle the response returned by the server
	 *
	 * @return	boolean		true on success
	 */
	postData: function (url, data, callback) {
	 	return this.editor.ajax.postData(url, data, callback, this);
	},
	/*
	 * Open a window with container iframe
	 *
	 * @param	string		buttonId: the id of the button
	 * @param	string		title: the window title (will be localized here)
	 * @param	object		dimensions: the opening dimensions od the window
	 * @param	string		url: the url to load ino the iframe
	 *
	 * @ return	void
	 */
	openContainerWindow: function (buttonId, title, dimensions, url) {
		this.dialog = new Ext.Window({
			id: this.editor.editorId + buttonId,
			title: this.localize(title) || title,
			cls: 'htmlarea-window',
			width: dimensions.width,
			border: false,
			iconCls: this.getButton(buttonId).iconCls,
			listeners: {
				afterrender: {
					fn: this.onContainerResize
				},
				resize: {
					fn: this.onContainerResize
				},
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
					// The content iframe
				xtype: 'box',
				height: dimensions.height-20,
				itemId: 'content-iframe',
				autoEl: {
					tag: 'iframe',
					cls: 'content-iframe',
					src: url
				}
			}
		});
		this.show();
	},
	/*
	 * Handler invoked when the container window is rendered or resized in order to resize the content iframe to maximum size
	 */
	onContainerResize: function (panel) {
		var iframe = panel.getComponent('content-iframe');
		if (iframe.rendered) {
			iframe.getEl().setSize(panel.getInnerWidth(), panel.getInnerHeight());
		}
	},
	/*
	 * Get the opening diment=sions of the window
	 *
	 * @param	object		dimensions: default opening width and height set by the plugin
	 * @param	string		buttonId: the id of the button that is triggering the opening of the window
	 *
	 * @return	object		opening width and height of the window
	 */
	getWindowDimensions: function (dimensions, buttonId) {
			// Apply default dimensions
		this.dialogueWindowDimensions = {
			width: 250,
			height: 250
		};
			// Apply default values as per PageTSConfig
		if (this.editorConfiguration.dialogueWindows) {
			Ext.apply(this.dialogueWindowDimensions, this.editorConfiguration.dialogueWindows);
		}
			// Apply dimensions as per button registration
		if (this.editorConfiguration.buttonsConfig[buttonId]) {
			Ext.apply(this.dialogueWindowDimensions, this.editorConfiguration.buttonsConfig[buttonId].dimensions);
		}
			// Apply dimensions as per call
		Ext.apply(this.dialogueWindowDimensions, dimensions);
			// Overrride dimensions as per PageTSConfig
		var buttonConfiguration = this.editorConfiguration.buttons[this.editorConfiguration.convertButtonId[buttonId]];
		if (buttonConfiguration && buttonConfiguration.dialogueWindow) {
			Ext.apply(this.dialogueWindowDimensions, buttonConfiguration.dialogueWindow);
		}
		return this.dialogueWindowDimensions;
	},
	/**
	 * Make url from module path
	 *
	 * @param	string		modulePath: module path
	 * @param	string		parameters: additional parameters
	 *
	 * @return	string		the url
	 */
	makeUrlFromModulePath: function(modulePath, parameters) {
		return modulePath + (modulePath.indexOf("?") === -1 ? "?" : "&") + this.editorConfiguration.RTEtsConfigParams + '&editorNo=' + this.editor.editorId + '&sys_language_content=' + this.editorConfiguration.sys_language_content + '&contentTypo3Language=' + this.editorConfiguration.typo3ContentLanguage + (parameters?parameters:'');
	},
	/**
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param	string		functionName: the name of the plugin function writing to the log
	 * @param	string		text: the text of the message
	 * @param	string		type: the typeof of message: 'log', 'info', 'warn' or 'error'
	 *
	 * @return	void
	 */
	appendToLog: function (functionName, text, type) {
		this.editor.appendToLog(this.name, functionName, text, type);
	},
	/*
	 * Add a config element to config array if not empty
	 *
	 * @param	object		configElement: the config element
	 * @param	array		configArray: the config array
	 *
	 * @return	void
	 */
	addConfigElement: function (configElement, configArray) {
		if (!Ext.isEmpty(configElement)) {
			configArray.push(configElement);
		}
	},
	/*
	 * Handler for Ext.TabPanel tabchange event
	 * Force window ghost height synchronization
	 * Working around ExtJS 3.1 bug
	 */
	syncHeight: function (tabPanel, tab) {
		var position = this.dialog.getPosition();
		if (position[0] > 0) {
			this.dialog.setPosition(position);
		}
	},
	/*
	 * Show the dialogue window
	 */
	show: function () {
			// Close the window if the editor changes mode
		this.dialog.mon(this.editor, 'HTMLAreaEventModeChange', this.close, this, {single: true });
		this.saveSelection();
		if (typeof(this.dialogueWindowDimensions) !== 'undefined') {
			this.dialog.setPosition(this.dialogueWindowDimensions.positionFromLeft, this.dialogueWindowDimensions.positionFromTop);
		}
		this.dialog.show();
		this.restoreSelection();
	},
	/*
	 * Close the dialogue window (after saving the selection, if IE)
	 */
	close: function () {
		this.saveSelection();
		this.dialog.close();
	},
	/*
	 * Dialogue window onClose handler
	 */
	onClose: function () {
		this.editor.focus();
		this.restoreSelection();
	 	this.editor.updateToolbar();
	},
	/*
	 * Handler for window cancel
	 */
	onCancel: function () {
		this.dialog.close();
		this.editor.focus();
	},
	/*
	 * Save selection
	 * Should be called after processing button other than Cancel
	 */
	saveSelection: function () {
			// If IE, save the current selection
		if (Ext.isIE) {
			this.savedRange = this.editor.getSelection().createRange();
		}
	},
	/*
	 * Restore selection
	 * Should be called before processing dialogue button or result
	 */
	restoreSelection: function () {
			// If IE, restore the selection saved when the window was shown
		if (Ext.isIE && this.savedRange) {
				// Restoring the selection will not work if the inner html was replaced by the plugin
			try {
				this.editor.getSelection().selectRange(this.savedRange);
			} catch (e) {}
		}
	},
	/*
	 * Build the configuration object of a button
	 *
	 * @param	string		button: the text of the button
	 * @param	function	handler: button handler
	 *
	 * @return	object		the button configuration object
	 */
	buildButtonConfig: function (button, handler) {
		return {
			xtype: 'button',
			text: this.localize(button),
			listeners: {
				click: {
					fn: handler,
					scope: this
				}
			}
		};
	}
});

return Plugin;

});