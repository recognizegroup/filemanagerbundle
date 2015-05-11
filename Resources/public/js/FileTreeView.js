"use strict";

var FileTreeView = function( config, element ){

    var defaults = {
        debug: false,
        elements: {
            title: ".titlebar",
            main: '.mainview',
            directories: '.directories'
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
    _titlebarElement: false,
    _contentElement: false,
    _uploadLink: false,


    // Keyboard accessability
    _keepFocusOnSearchbar: false,
    _keepFocusOnDirectories: false,
    _keepFocusOnTitlebar: false,

    /**
     * Initializes the filetreeview
     *
     * @param config                        Configuration object
     * @param element                       The main HTML element
     */
    init: function( config, element ){
        this._debug = config.debug;
        this._container = element;
        this._directoryElement = $( config.elements.directories );
        this._titlebarElement = $( config.elements.title );
        this._contentElement = $( config.elements.main );
        this._eventHandler = config.eventHandler;

        if( typeof config.filerowFormat === "function" ){
            this._formatFilerow = config.filerowFormat;
        }

        if( typeof config.renamerowFormat === "function" ){
            this._formatRenamerow = config.renamerowFormat;
        }

        if( typeof config.titledirectoryFormat === "function" ){
            this._formatTitleDirectory = config.titledirectoryFormat;
        }

        if( typeof config.searchelementFormat === "function" ){
            this._formatSearchelement = config.searchelementFormat;
        }

        if( typeof config.uploadbuttonFormat === "function" ){
            this._formatUploadButton = config.uploadbuttonFormat;
        }

        if( typeof config.createdirectoryFormat === "function" ){
            this._formatCreatedirectoryButton = config.createdirectoryFormat;
        }


        this._registerEvents();
    },

    /**
     * Formats the data of the file into an HTML element - Can be replaced using the config variables
     *
     * @param file                          A file object
     * @returns {string}                    An html string
     * @private
     */
    _formatFilerow: function( file ){
        return "<p class=\"filemanagerrow\"><span>" + file.path + "</span></p>";
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
     * Formats the search element in the titlebar
     *
     * @param searchelement                 An html element with the input field
     * @returns {string}                    An html string
     * @private
     */
    _formatSearchelement: function( searchelement ){
        return searchelement;
    },

    /**
     * Formats the data of the current directory into an HTML element - Can be replaced using the config variables
     *
     * @param current_directory             The current directory
     * @returns {string}                    An html string
     * @private
     */
    _formatTitleDirectory: function( current_directory ){
        return "<p class=\"filemanagerrow\"><span>" + current_directory + "</span></p>";
    },

    /**
     * Outputs the button that should be in the form
     *
     * @returns {string}                    An html string
     * @private
     */
    _formatUploadButton: function(){
        return '<a class="btn filemanager_uploading">Uploaden' + '<input type="file" name="filemanager_upload"/>' + "</a>";
    },

    /**
     * Outputs the button that creates a new directory row in the content area
     *
     * @returns {string}                    An html string
     * @private
     */
    _formatCreatedirectoryButton: function(){
        return "<a>Create directory</a>";
    },

    /**
     * Destroy and recreate the titlebar view
     */
    refreshTitlebar: function( current_directory ){
        var self = this;

        this.debug("Refreshing titlebar with directory " + current_directory );

        if( this._titlebarElement.length > 0 ){
            this._titlebarElement.empty();

            var directoryelement = $( this._formatTitleDirectory( current_directory ) );
            directoryelement.appendTo( this._titlebarElement )
                .on('click', function(event){
                    var higherdirectory = self._getHigherDirectory( current_directory );
                    self._eventHandler.trigger('filemanager:view:open', { directory: higherdirectory, isSynchronized: true });

                }).on('keydown', function(event){

                    // ENTER
                    if( event.keyCode == 13 ){
                        event.preventDefault();

                        // Force the active state on enter
                        $( event.target).addClass('active').trigger('mousedown');
                    }

                }).on('keyup', function(event){
                    if( event.keyCode == 13 ){

                        // Make sure to keep the focus on the upper directory on refresh
                        self._keepFocusOnTitlebar = true;
                        $( event.currentTarget).removeClass('active').trigger('click');
                    }
                });

            var createbuttonstring = this._formatCreatedirectoryButton();
            var createdirectorybutton = $( createbuttonstring );
            createdirectorybutton.on('click', { directory: current_directory }, function( event ){
                self._createDirectory( event.data.directory );
            });
            createdirectorybutton.appendTo( this._titlebarElement );

            var searchinputstring = '<input type="search" name="filemanager_search" />';
            var searchinput = $( searchinputstring );
            searchinput.on('search', { directory: current_directory }, function( event ){
                    self._searchEvent( event );

                    self._keepFocusOnSearchbar = true;
                }).on('keyup', { directory: current_directory }, function( event ){

                    // Search on Enter
                    if( event.keyCode == 13 ){
                        self._searchEvent( event );
                        self._keepFocusOnSearchbar = true;
                    }
                });
            var searchelement = this._formatSearchelement( searchinput );
            searchelement.appendTo( this._titlebarElement );

            var uploadstring = '<form enctype="multipart/form-data" method="POST" action="/admin/fileapi/create">' +
                '<input type="hidden" name="filemanager_directory" value="' + current_directory + '" />' +
                this._formatUploadButton() +
                '</form>';

            var uploadelement = $( uploadstring );
            uploadelement.addClass('upload-container');
            uploadelement.appendTo( this._titlebarElement );

            // Keyboard focus handling
            if( self._keepFocusOnTitlebar == true ){
                directoryelement.focus();
                self._keepFocusOnTitlebar = false;
                self._keepFocusOnSearchbar = false;

            } else if ( self._keepFocusOnSearchbar == true ){
                searchinput.focus();
            }
        }
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
                'multiple': false}
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
        var createdirectoryrow = $( this._formatRenamerow( directory ) );

        // Add an enter event to the input
        var self = this;
        var createdirectoryelement = createdirectoryrow.find('input[name=directory_name]');
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

        this._contentElement.prepend( createdirectoryrow );
        createdirectoryelement.focus();
    },

    /**
     * Destroy and recreate the main file views
     *
     * @param content
     */
    refreshContent: function( content ){
        var self = this;
        if( this._contentElement.length > 0 ){

            // Clear the view before filling it
            this._contentElement.empty();

            // Clear all the context menus
            $.contextMenu( 'destroy' );

            for( var i = 0, length = content.length; i < length; i++ ){

                var file = $.extend({}, content[i]);
                file.size = this._filesizeFormat( file.size );
                var filerow = this._formatFilerow( file );
                var rowclass = 'filerow-' + i;

                var rowelement = $(filerow)
                    .addClass( rowclass )
                    .appendTo( this._contentElement )
                    .on('click',{ file: file }, function( evt ) {
                        $(evt.currentTarget).toggleClass('selected');

                        // On mobile, a click is a double click
                        if ($(window).width() <= 768) {
                            self._openContentEvent(evt);
                        }
                    }).on('keyup', { file: file }, function( event ){

                        // ENTER
                        if( event.keyCode == 13){
                            self._openContentEvent( event );

                            // Make sure to keep the focus on the upper directory on refresh
                            self._keepFocusOnTitlebar = true;
                        }

                    }).on('dblclick', { file: file }, function( evt ){
                        self._openContentEvent( evt );
                    });

                self._addContextmenuToRow( "." + rowclass, file );
            }
        }
    },

    /**
     * Add a contextual menu that opens on the right click to a filerow
     *
     * @param selector             The css class of the filerow
     * @param file                 The file object
     * @private
     */
    _addContextmenuToRow: function( selector, file ){
        var self = this;

        $.contextMenu({
            selector: selector,
            file: file,
            callback: function(key, options) {
                switch( key ){
                    case "rename":
                        self.createRenamerow( options.selector, options.file );
                        break;
                }
            },
            items: {
                "rename": {name: "Rename", icon: "edit"}
            }
        });
    },

    /**
     * Turns row
     *
     * @param selector          The css class of the filerow to swap
     * @param file
     */
    createRenamerow: function( selector, file ){
        var filerow = this._contentElement.find( selector );
        var self = this;

        var copiedfile = $.extend({}, file );

        copiedfile.name = '<input type="text" name="file_name" value="' + file.name + '"/>';
        var renameelement = $( self._formatRenamerow( copiedfile ) );
        var renameinput = renameelement.find('input');
        renameinput.on("keydown", {directory: file.path }, function( event ){

            // ENTER
            if( event.keyCode == 13 ){
                if( event.target.value != "" ){
                    console.log( "Rename to " + event.target.value );
                }

                // Replace the inputrow with the regular filerow
                self._contentElement.find(renameelement).remove();
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
        if( typeof event.data.file.type == "undefined" || event.data.file.type == "dir" ) {
            var synchronized = false;
            this._eventHandler.trigger('filemanager:view:open', {directory: path, isSynchronized: synchronized} );
        } else {
            this._eventHandler.trigger('filemanager:view:select', { file: path });
        }
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
            this._eventHandler.trigger('filemanager:view:search', { directory: event.data.directory, query: querystring });
        } else {
            this._eventHandler.trigger('filemanager:view:open', { directory: event.data.directory, isSynchronized: false });
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
            alert( response.message );
        });

        this._eventHandler.register('filemanager:model:directories_changed', function( jstreedata ){
            self.refreshDirectories( jstreedata );
        });

        this._eventHandler.register('filemanager:model:content_changed', function( content ){
            self.refreshContent( content );
        });

        this._eventHandler.register('filemanager:model:path_changed', function( current_directory ){
            self.refreshTitlebar( current_directory );
        });
    }
};