"use strict";

var FileTreeView = function( config, element ){

    var defaults = {
        debug: false,
        i18n: {
            rename: "Rename",
            cut: "Cut",
            paste: "Paste",
            download: "Download",
            "delete": "Delete"
        }
    };

    this.options = $.extend(true, defaults, config);
    this.init( this.options, element );
};

FileTreeView.prototype = {

    _debug: false,
    _eventHandler: false,
    _container: false,

    _directoryElement: false,
    _contentElement: false,
    _uploadLink: false,
    _uploadFunctionalityReference: null,

    _viewFormat: "list",

    // Keyboard accessability
    _keepFocusOnDirectories: false,
    _keepFocusOnContent: false,

    _currentDirectory: "",
    _currentContent: [],
    _searching: false,
    _searchQuery: "",
    _apiCalled: false,

    _isMultiple: false,
    _selectedFiles: [],

    _editContext: {
        mode: "none",
        selector: false,
        file: false
    },

    _i18n: {},

    /**
     * Initializes the filetreeview
     *
     * @param config                        Configuration object
     * @param element                       The main HTML element
     */
    init: function( config, element ){
        var self = this;

        this._debug = config.debug;
        this._container = $( element );
        this._directoryElement = $("[data-fm-value=directories]");

        this._eventHandler = config.eventHandler;

        if( typeof config.filerowFormat === "function" ){
            this._formatFilerow = config.filerowFormat;
        }

        if( typeof config.fileCellFormat === "function" ){
            this._formatFilecell = config.fileCellFormat;
        }

        if( typeof config.renamerowFormat === "function" ){
            this._formatRenamerow = config.renamerowFormat;
        }

        if( typeof config.renamecellFormat === "function" ){
            this._formatRenamecell = config.renamecellFormat;
        }

        if( typeof config.uploadFormat === "function" ){
            this._formatUpload = config.uploadFormat;
        }

        if( typeof config.i18n === "object" ){
            this._i18n = config.i18n;
        }

        this._setOverviewLayout("list");
        this._addFunctionality();
        this._registerEvents();
    },

    /**
     * Formats the data of the file into an HTML element used in the listview - Can be replaced using the config variables
     *
     * @param file                          A file object
     * @returns {string}                    An html string
     * @private
     */
    _formatFilerow: function( file ){
        return "<p class=\"filemanagerrow\"><span>" + file.path + "</span></p>";
    },

    /**
     * Formats the data of the file into an HTML element used in the gridview - Can be replaced using the config variables
     *
     * @param file                          A file object
     * @returns {string}                    An html string
     * @private
     */
    _formatFilecell: function( file ){
        return "<p class=\"filemanagercell\"><span>" + file.path + "</span></p>";
    },

    /**
     * Formats the row where inline editting is taking place
     *
     * @param input                          A file object
     * @returns {string}                    An html string
     * @private
     */
    _formatRenamerow: function( file ){
        return "<p class=\"filemanagerrow\"><span>" + file.name + "</span></p>";
    },

    /**
     * Formats the cell where inline editting is taking place
     *
     * @param input                          A file object
     * @returns {string}                    An html string
     * @private
     */
    _formatRenamecell: function( file ){
        return "<p class=\"filemanagercell\"><span>" + file.name + "</span></p>";
    },

    /**
     * Destroy and recreate the titlebar view
     */
    refreshTitlebar: function( current_directory ){
        var self = this;

        this.debug("Refreshing directory string to " + current_directory );
        this._currentDirectory = current_directory;
        $("[data-fm-value=current_directory]").text( "/" + current_directory );

        // Update the uploading location
        this._addUploadFunctionality();
    },

    /**
     * Destroy and recreate the jsTree view
     *
     * @param jstreedata
     */
    refreshDirectories: function( jstreedata ){
        var self = this;

        // Update the view with the new data
        if( this._directoryElement.length > 0
            && typeof this._directoryElement.jstree === "function" ){

            this._directoryElement.jstree('destroy').jstree({ 'core': {
                'data' : jstreedata,
                'multiple': false
            },'plugins': []

            }).on('dblclick.jstree', function( event ){

                self._jstreeOpenEvent( event );
            }).on('keyup.jstree', function( event ){

                // Open on Enter
                if( event.keyCode == 13 ){
                    self._jstreeOpenEvent( event );
                }
            });
        }
    },

    /**
     * Create a directory creation row in the content
     * Which creates a new directory when the enter key is pressed
     */
    _createDirectory: function( path ){
        var createdirectorystring = '<input type="text" name="directory_name"/>';

        // Render a row in the contents with the filename as an inputfield
        var directory = {
            name: createdirectorystring,
            type: "dir",
            date_modified: "",
            size: "",
            children: {}
        };

        var createdirectorycontainer;
        if( this._viewFormat == "list"){
            createdirectorycontainer = $( this._formatRenamerow( directory ) );
        } else if( this._viewFormat == "grid") {
            createdirectorycontainer = $( this._formatRenamecell( directory ) );
        }

        // Add an enter event to the input
        var self = this;
        var createdirectoryelement = createdirectorycontainer.find('input[name=directory_name]');
        createdirectoryelement.on("keydown", {directory: path }, function( event ){

            // ENTER
            if( event.keyCode == 13 ){
                if( event.target.value != "" ){
                    self._eventHandler.trigger('filemanager:view:create', { directory: event.data.directory, name: event.target.value });
                }

                // Remove the directory element on enter
                self._contentElement.children().eq(0).remove();
            }
        }).on("blur", function( event ){

            // Remove the directory element when the textfield loses focus
            self._contentElement.children().eq(0).remove();
        });

        this._contentElement.prepend( createdirectorycontainer );
        createdirectoryelement.focus();
    },

    /**
     * Destroy and recreate the main file views
     *
     * @param content
     */
    refreshContent: function( content ){
        var self = this;

        if( content !== undefined ){
            this._currentContent = content;
        }

        if( this._contentElement.length > 0 ){

            // Clear the view before filling it
            this._contentElement.empty();

            // Clear all the context menus
            $.contextMenu( 'destroy' );

            for( var i = 0, length = self._currentContent.length; i < length; i++ ){
                var file = $.extend({}, self._currentContent[i]);
                var viewfile = $.extend({}, self._currentContent[i]);

                // Add a span around the searched word
                if( self._searching == true ){
                    viewfile.name = viewfile.name.replace(this._searchQuery, '<span class="searchquery">' + this._searchQuery + '</span>');
                }

                viewfile.size = this._filesizeFormat( viewfile.size );

                // Use the correct format for the overview
                var filestring = "";
                if( self._viewFormat == "list"){
                    filestring = this._formatFilerow( viewfile );
                } else {
                    filestring = this._formatFilecell( viewfile );
                }

                var rowselector = "file-" + file.directory + file.name;
                var fileselector = "[data-fm-functionality=\"" + rowselector + "\"]";

                var fileelement = $( filestring )
                    .attr("data-fm-functionality", rowselector )
                    .appendTo( this._contentElement )
                    .on('click',{ file: file, selector: fileselector }, function( event ) {

                        // On mobile, a click is a double click
                        if ($(window).width() <= 768) {
                            self._openContentEvent( event );
                        } else {
                            self._toggleSelection( event );
                        }

                    }).on('keyup', { file: file, selector: fileselector }, function( event ){

                        // ENTER
                        if( event.keyCode == 13){

                            // Keep the focus on the content items if we are using a keyboard
                            self._keepFocusOnContent = true;

                            self._openContentEvent( event );
                        }

                    // Add cut and paste shortcuts
                    }).on('keydown', { file: file, selector: fileselector }, function( event ){

                        // CTRL X
                        if( event.ctrlKey && event.keyCode == 88 ){
                            self._setCutMode( event.data.selector, event.data.file );

                        // CTRL V
                        } else if ( event.ctrlKey && event.keyCode == 86 && self._editContext.mode !== "none" ){
                            self._pasteMode( event.data.selector, event.data.file );
                        }

                    }).on('dblclick', { file: file, selector: fileselector }, function( event ){
                        self._openContentEvent( event );
                    });

                // Make sure the selected files stay selected when the content is refreshed
                for( var j = 0, jlength = this._selectedFiles.length; j < jlength; j++ ){
                    if( this._selectedFiles[ j ].selector == fileselector ){
                        fileelement.addClass("selected");
                    }
                }

                self._addContextmenuToElement( fileselector , file );

                // Ensure we keep focus on the content area
                if( i == 0 && self._keepFocusOnContent == true ){
                    fileelement.focus();
                    self._keepFocusOnContent = false;
                }
            }
        }

        this._setEditStyling();
        this._updateVisibility();
        self._addContextmenuToElement( "[data-fm-functionality=directory_up]",
            { type: "dir", name: "", directory: self._currentDirectory}, true );
        self._eventHandler.trigger("filemanager:view:rendered");
    },

    /**
     * Add a contextual menu that opens on the right click to a filerow
     *
     * @param selector             The css class of the filerow
     * @param file                 The file object
     * @param pasteonly            Whether to only allow pasting or all other options
     * @private
     */
    _addContextmenuToElement: function( selector, file, pasteonly ){
        var self = this;

        $.contextMenu({
            selector: selector,
            build: function () {

                var items = {
                    "rename": {name: self._i18n.rename, icon: "rename"},
                    "cut": {name: self._i18n.cut, icon: "cut"},
                    "paste": {name: self._i18n.paste, icon: "paste"}
                };

                if( file.type !== "dir" ){
                    items.download = {name: self._i18n.download, icon: "download"};
                }

                items.seperator = "---------";
                items["delete"] = {name: self._i18n.delete, icon: "delete"};

                if( pasteonly === true ){
                    items = {};
                    if( self._editContext.mode !== "none" ){
                        items.paste = {name: self._i18n.paste, icon: "paste"};
                    }

                } else if( self._editContext.mode !== "none" ){
                    items.paste.disabled = false;
                } else {
                    items.paste.disabled = true;
                }


                return {
                    file: file,
                    callback: function (key, options) {
                        switch (key) {
                            case "rename":
                                self.createRenamerow(options.selector, options.file);
                                break;
                            case "cut":
                                self._setCutMode(options.selector, options.file);
                                break;
                            case "paste":
                                self._pasteMode(options.selector, options.file);
                                break;
                            case "delete":

                                self._eventHandler.trigger('filemanager:view:delete', {file: options.file});
                                break;
                            case "download":
                                self._eventHandler.trigger('filemanager:view:download', {file: options.file});
                                break;
                        }
                    },
                    items: items
                }
            }
        });
    },

    /**
     * Turns the current filerow into a row with an inputfield instead of the filename
     *
     * @param selector          The css class of the filerow to swap
     * @param file              The file linked to the filerow
     */
    createRenamerow: function( selector, file ){
        var filerow = this._contentElement.find( selector );
        var self = this;

        var copiedfile = $.extend({}, file );

        copiedfile.name = '<input type="text" name="file_name" value="' + file.name + '"/>';
        var renameelement;
        if( this._viewFormat == "list"){
            renameelement = $( self._formatRenamerow( copiedfile ) );
        } else if( this._viewFormat == "grid") {
            renameelement = $( self._formatRenamecell( copiedfile ) );
        }

        var renameinput = renameelement.find('input');
        renameinput.on("keydown", {file: file }, function( event ){

            // ENTER
            if( event.keyCode == 13 ){
                if( event.target.value != "" ){
                    self._renameEvent( event );
                }

                // Replace the inputrow with the regular filerow
                self._contentElement.find( renameelement ).remove();
                self._contentElement.find( filerow ).show();

            }
        }).on("blur", function( event ){

            // Replace the inputrow with the regular filerow
            self._contentElement.find(renameelement).remove();
            self._contentElement.find( filerow ).show();
        });

        self._contentElement.find( filerow).hide().after( renameelement );

        // Reset the input to make sure the blinking caret gets set at the end of the input on focus
        var value =  renameinput.val();
        renameinput.val('').focus().val( value );
    },

    /**
     * Open a directory from the mainview
     *
     * @param event
     * @private
     */
    _openContentEvent: function( event ){
        var directory = event.data.file.directory;
        var path = event.data.file.path;
        if( path == false ){
            path = event.data.file.directory + event.data.file.name;
        }

        if( typeof event.data.file.type == "undefined" || event.data.file.type == "dir" ) {
            var synchronized = false;
            this._eventHandler.trigger('filemanager:view:open', {directory: path, isSynchronized: synchronized} );
        } else {
            this._toggleSelection( event );
        }
    },

    /**
     * Toggle the selection
     *
     * @param event
     * @private
     */
    _toggleSelection: function( event ){
        if( this._isFileSelected( event.data.file ) ){
            this._deselectEvent ( event );
        } else  {
            this._selectEvent ( event );
        }
    },

    /**
     * Select a file
     *
     * @param event
     * @private
     */
    _selectEvent: function( event ){
        if( this._isMultiple == false ){
            this._clearSelection();
        }

        this._selectedFiles.push( {selector: event.data.selector, file: event.data.file } );
        $( event.data.selector ).addClass("selected");

        this._eventHandler.trigger('filemanager:view:select', { path: event.data.file.path, file: event.data.file });
    },

    /**
     * Check if the file is selected
     *
     * @param file
     * @private
     */
    _isFileSelected: function( file ){
        for( var i = 0, length = this._selectedFiles.length; i < length; i++ ){
            if( file == this._selectedFiles[i].file ){
                return true;
            }
        }

        return false;
    },

    /**
     * Deselect a file
     *
     * @param event
     * @private
     */
    _deselectEvent: function( event ){

        // Rebuild the selection array
        var newSelectedFiles = [];
        for( var i = 0, length = this._selectedFiles.length; i < length; i++ ){
            if( event.data.file == this._selectedFiles[i].file ) {
                $( event.data.selector ).removeClass("selected");
            } else {
                newSelectedFiles = this._selectedFiles[i];
            }
        }

        this._selectedFiles = newSelectedFiles;
        this._eventHandler.trigger('filemanager:view:deselect', { path: event.data.file.path, file: event.data.file });
    },

    /**
     * Clear all the previously selected files and their styling
     *
     * @private
     */
    _clearSelection: function(){
        for( var i = 0, length = this._selectedFiles.length; i < length; i++ ){
            $( this._selectedFiles[i].selector).removeClass("selected");
        }

        this._selectedFiles = [];
    },

    /**
     * Open a directory from the jstree elements
     *
     * @param event
     * @private
     */
    _jstreeOpenEvent: function( event ){
        var node = $( event.target).closest('li');
        if( node.length > 0 ){
            var filepath = node.attr('data-path');
            var synchronized = node.attr('data-synchronized') != "false";

            if( typeof filepath !== "undefined" ){
                this._eventHandler.trigger('filemanager:view:open', {directory: filepath, isSynchronized: !synchronized} );
            }
        }
    },

    /**
     * Search a directory with a search query
     *
     * @param event
     * @private
     */
    _searchEvent: function( event ){
        var querystring = event.target.value;

        // Only search if the value isn't empty
        if( querystring != "" ){
            this._eventHandler.trigger('filemanager:view:search', { directory: this._currentDirectory, query: querystring });
            $("[data-fm-value=search_query]").text( querystring );
        } else {
            this._eventHandler.trigger('filemanager:view:open', { directory: this._currentDirectory, isSynchronized: false });
        }
    },

    /**
     * Rename a file or directory
     *
     * @param event
     * @private
     */
    _renameEvent: function( event ){
        var newname = event.target.value;

        // Only rename if the value isn't empty
        if( newname != "" ){
            this._eventHandler.trigger('filemanager:view:rename', { file: event.data.file, newname: newname });
        }
    },

    /**
     * Gets the directory above the directory using its path
     *
     * @param directory_path
     * @returns {string}
     * @private
     */
    _getHigherDirectory: function( directory_path ){
        var pathnodes = directory_path.split('/');

        // Filter out all the spaces
        pathnodes = pathnodes.filter( function(value){
            return value != "";
        });

        var path = "";
        for( var i = 0, length = pathnodes.length - 1; i < length; i++ ){
            path += pathnodes[i];

            if( i !== length - 1 ){
                path += "/";
            }
        }

        return path;
    },

    /**
     * Returns a human readable string for the byte amount of a file
     *
     * @param bytes
     * @returns {string}
     * @private
     */
    _filesizeFormat: function( bytes ){
        var kbsize = 1024;
        var mbsize = 1024 * kbsize;
        var gbsize = 1024 * mbsize;

        var filesize = "";
        if( isNaN( bytes ) == false ){
            bytes = parseInt( bytes );

            if( bytes < kbsize ){
                filesize = bytes + " B";
            } else if (bytes < mbsize ){
                filesize = (bytes / kbsize).toFixed(1) + " KB";
            } else if (bytes < gbsize ){
                filesize = (bytes / mbsize).toFixed(1) + " MB";
            } else {
                filesize = (bytes / gbsize).toFixed(1) + " GB";
            }
        }

        return filesize;
    },

    /**
     * Show the correct display - Either a list or a gridview
     *
     * @private
     */
    _setOverviewLayout: function( overviewlayout ){
        if( overviewlayout == "list" || overviewlayout == "grid"){
            this._viewFormat = overviewlayout;

            if( this._viewFormat == "list"){
                $("[data-fm-functionality=set_list]").addClass("selected");
                $("[data-fm-functionality=set_grid]").removeClass("selected");

                this._contentElement = $("[data-fm-value=list_content]");

            } else if( this._viewFormat == "grid" ){
                $("[data-fm-functionality=set_grid]").addClass("selected");
                $("[data-fm-functionality=set_list]").removeClass("selected");

                this._contentElement = $("[data-fm-value=grid_content]");
            }

            this._updateVisibility();
        } else {
            this.errorLog( "Overview layout can only be a list or a grid" );
        }
    },

    /**
     * Reset the previious styling on a row that is being copied or cut
     *
     * @private
     */
    _resetEditStyles: function(){
        this._editMode = "none";
        if( this._editContext.selector !== false ){
            $( this._editContext.selector).removeClass("mode-cut").removeClass("mode-copy");
            this._editContext.selector = false;
        }

        this._editContext.file = false;
    },

    /**
     * Set the edit styling according to the editContext state
     *
     * @private
     */
    _setEditStyling: function(){
        var context = this._editContext;

        if( context.file !== false && context.selector !== false ){
            switch( context.mode ){
                case "cut":
                    $( context.selector ).addClass("mode-cut");
                    break;
                case "copy":
                    $( context.selector ).addClass("mode-copy");
                    break;
                case "none":
                    this._resetEditStyles();
                    break;
            }
        }
    },

    /**
     * Select a single file for cutting and moving
     *
     * @param selector                  The HTML element linked to the file
     * @param file
     * @private
     */
    _setCutMode: function( selector, file ){
        this._resetEditStyles();

        this._editContext = {
            mode: "cut",
            selector: selector,
            file: file
        };

        this._setEditStyling();
    },

    /**
     * Select a single file for copying and moving
     *
     * @param selector                  The HTML element linked to the file
     * @param file
     * @private
     */
    _setCopyMode: function( selector, file ){
        this._resetEditStyles();

        this._editContext = this._editContext = {
            mode: "copy",
            selector: selector,
            file: file
        };

        this._setEditStyling();
    },

    /**
     * Move the directory over to the new place
     *
     * @param selector
     * @param file
     * @private
     */
    _pasteMode: function( selector, file ){
        if( this._editContext.mode !== "none" && this._editContext.file !== false ){
            var newlocation = file.directory;
            if( file.type == "dir"){
                newlocation += file.name;
            }

            this._eventHandler.trigger("filemanager:view:move", {
                file: this._editContext.file,
                newlocation: newlocation
            });
        }

        this._resetEditStyles();
    },

    /**
     * Update the visibility of the data-fm-show tags
     * The available tags are laid out below
     *
     * no_content - Show this element if we don't have content in this directory
     * no_search_results - Show this element if we don't have any search results
     * list_view - Show this if the overview layout mode is set to list view
     * grid_view - Show this if the overview layout mode is set to grid view
     * search_results - Show this element if we have search results
     * not_root - Show this element if we aren't in the root folder
     *
     * @private
     */
    _updateVisibility: function(){
        var showtag = function( tag ){
            return "[data-fm-show=" + tag + "]";
        };

        if( this._viewFormat == "list"){
            $( showtag("list_view") ).show();
            $( showtag("grid_view") ).hide();
        } else if( this._viewFormat == "grid" ) {
            $( showtag("list_view") ).hide();
            $( showtag("grid_view") ).show();
        }

        $( showtag("no_content") ).hide();
        $( showtag("no_search_results") ).hide();
        if( this._currentContent.length == 0 ){
            if( this._searching == false ){
                if( this._apiCalled ){
                    $( showtag("no_content") ).show();
                }
            } else {
                $( showtag("no_search_results") ).show();
            }
        } else {
            if( this._searching == true ){
                $( showtag("search_results") ).show();
            }
        }

        if( this._currentDirectory !== ""){
            $( showtag("no_root") ).show();
        } else {
            $( showtag("no_root") ).hide();
        }
    },

    /**
     * Add listeners and functionality to the elements designated to have this functionality
     * Functionalities are set by adding a data-fm-functionality attribute with one of the functionalities below
     *
     * search - The search input field
     * directory_up - Button that moves up a directory
     * refresh - Refreshes the current directory
     * create_directory - Button that creates a directory row in the filemanager
     * set_grid - Set the content view as grid
     * set_list - Set the content view as a list
     * sort_filename - Button that toggles the sorting on filename
     * sort_* - Button that toggles the sorting on a property of a file ( For example, sort_date_modified )
     *
     * @private
     */
    _addFunctionality: function(){
        var self = this;

        // Shared button events for keyboard functionality
        var keydownEvent = function(event){

            // ENTER
            if( event.keyCode == 13 ){
                event.preventDefault();

                // Force the active state on enter
                $( event.target).addClass('active').trigger('mousedown');
            }
        };

        var keyupEvent = function(event){
            if( event.keyCode == 13 ){
                $( event.currentTarget).removeClass('active').trigger('click');
            }
        };

        // Directory up button
        $("[data-fm-functionality=directory_up]").on('click', function(event){
            self._eventHandler.trigger('filemanager:view:directory_up',{});
        }).on('keydown', keydownEvent).on('keyup', keyupEvent);

        // Refresh button
        $("[data-fm-functionality=refresh]").on("click", function(){
            self._eventHandler.trigger("filemanager:view:refresh", {});
        }).on('keydown', keydownEvent).on('keyup', keyupEvent);

        // Create directory button
        $("[data-fm-functionality=create_directory]").on("click", function( event ){
            self._createDirectory( self._currentDirectory );
        }).on('keydown', keydownEvent).on('keyup', keyupEvent);

        // Set the overview as a list view
        $("[data-fm-functionality=set_list]").on("click", function( event ){
            self._setOverviewLayout("list");

            self.refreshContent();
        }).on('keydown', keydownEvent).on('keyup', keyupEvent);

        // Set the overview as a list view
        $("[data-fm-functionality=set_grid]").on("click", function( event ){
            self._setOverviewLayout("grid");

            self.refreshContent();
        }).on('keydown', keydownEvent).on('keyup', keyupEvent);


        // Sort button for properties
        $("[data-fm-functionality^='sort_']").on("click", function( event ){
            var funcproperty = $( event.currentTarget).attr("data-fm-functionality");
            var property = funcproperty.substring( 5 );

            if( property === "filename" ){
                self._eventHandler.trigger("filemanager:view:sort");
            } else {
                self._eventHandler.trigger("filemanager:view:sort", {
                    sortfunction: function(a, b){
                        if(a[ property ] > b[ property ]){
                            return -1;
                        } else if(a[ property ] < b[ property ]){
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                });
            }
        }).on("keydown", keydownEvent).on("keyup", keyupEvent);

        // Search input
        $("input[data-fm-functionality=search]").on('search', { directory: self._currentDirectory }, function( event ) {
            self._searchEvent(event);

        }).on('keydown', function( event ){

            // Disable ENTER on keydown
            if( event.keyCode == 13){
                event.preventDefault();
            }

        }).on('keyup', { directory: self._currentDirectory }, function( event ){

            // Search on Enter
            if( event.keyCode == 13 ){
                self._searchEvent( event );
            }
        });

        self._addUploadFunctionality();
    },

    /**
     * Add the AJAX uploading functionality
     * Functionalities are set by adding a data-fm-functionality attribute with one of the functionalities below
     *
     * upload_progress - A progress bar, the width gets updated from 0% to 100% during uploading
     * upload_button - Button that opens the filemanager and starts the uploading process if a file was selected
     * abort_upload - Button that aborts the current upload
     *
     * @private
     */
    _addUploadFunctionality: function(){
        var self = this;

        if( this._uploadFunctionalityReference !== null ){
            this._uploadFunctionalityReference.destroy();
        }

        var progressBar = $("[data-fm-functionality=upload_progress]");

        // Add the AJAX upload functionality
        var uploader = new ss.SimpleUpload({
            url: "/admin/fileapi/create",
            name: "filemanager_upload",
            method: 'POST',
            hoverClass: 'focus',
            focusClass: 'active',
            multipart: true,
            data: {
                filemanager_directory: self._currentDirectory
            },
            responseType: 'json',
            dropzone: "filemanager_view",
            debug: self._debug,
            startXHR: function() {

                if( progressBar.length > 0 ){
                    progressBar.css("width", "0%");
                    this.setProgressBar( progressBar );
                }

                self._eventHandler.trigger("filemanager:api:uploading");
            },

            onComplete: function(filename, response){
                self._eventHandler.trigger("filemanager:api:upload_done");
                self._eventHandler.trigger('filemanager:view:ajax_upload', {response: response, directory: self._currentDirectory } );
            },

            onAbort: function(){
                self._eventHandler.trigger("filemanager:api:upload_done");
            },

            onError: function( filename, errorType, status, statusText, response ){
                self._eventHandler.trigger("filemanager:api:upload_done");

                response = JSON.parse( response );
                if( response != false ){
                    self._eventHandler.trigger('filemanager:api:error', {message: response.data.message, status: statusText, statuscode: status });
                }
            }
        });
        uploader.setAbortBtn( $("[data-fm-functionality=abort_upload]") );

        if( this._uploadFunctionalityReference === null ){
            // Ajax upload button
            var uploadbutton = $("[data-fm-functionality=upload_button]");
            if( uploadbutton.length !== 0 ){

                // Keyboard focus for AJAX button
                uploadbutton.on("click", function(){
                    $("input[name=filemanager_upload]").trigger("click");

                }).on("keydown", function( event ){

                    // ENTER
                    if( event.keyCode == 13 ) {
                        uploadbutton.addClass('active');
                        event.preventDefault();
                    }
                }).on("keyup", function( event ){

                    // ENTER
                    if( event.keyCode == 13 ){
                        uploadbutton.removeClass('active');
                        $("input[name=filemanager_upload]").trigger("click");
                    }
                });
            }
        }

        // Save the reference to the SimpleAjaxUploader to prevent multiple listeners being linked to the file uploading
        this._uploadFunctionalityReference = uploader;
    },

    /**
     * Displays debug data
     * @param debug_message
     */
    debug: function( debug_message ) {
        if( typeof debug_message === "string" ){
            debug_message =  "FileTreeView: " + debug_message;
        }

        if(this._debug) console.log( debug_message );
    },

    /**
     * Log an error
     *
     * @param errormessage
     */
    errorLog: function( errormessage ) {
        console.error( errormessage );
    },

    /**
     * Register the events that listen for changes in the data and for errors from the api
     *
     * @private
     */
    _registerEvents: function(){
        var self = this;

        this._eventHandler.register('filemanager:api:error', function( response ) {
            self._apiCalled = true;

            alert( response.message );
        });

        this._eventHandler.register('filemanager:model:directories_changed', function( jstreedata ){
            self._apiCalled = true;

            // Whenever we moved to another directory, turn off the searching mode
            self._searching = false;
            self._searchQuery = "";

            self.refreshDirectories( jstreedata );
        });

        // Listen to our own search event to make searching via the jquery function possible
        this._eventHandler.register("filemanager:view:search", function( event ){
            self._apiCalled = true;

            self._searching = true;
            self._searchQuery = event.query;
        });

        this._eventHandler.register('filemanager:model:content_changed', function( content ){
            self._apiCalled = true;

            self.refreshContent( content );
        });

        this._eventHandler.register('filemanager:model:path_changed', function( current_directory ){
            self._apiCalled = true;

            self.refreshTitlebar( current_directory );
        });
    }
};