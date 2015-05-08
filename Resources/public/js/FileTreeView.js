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

        if( typeof config.titledirectoryFormat === "function" ){
            this._formatTitleDirectory = config.titledirectoryFormat;
        }

        if( typeof config.searchelementFormat === "function" ){
            this._formatSearchelement = config.searchelementFormat;
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
                '<input type="file" name="filemanager_upload" />' +
                '<input type="submit" value="Uploaden" />' +
                '</form>';
            var uploadelement = $( uploadstring );
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
     * The current sorting function
     *
     * @param a
     * @param b
     * @returns {number}
     * @private
     */
    _sortingFunction: function( a, b ){
        var atype = a.type;
        var btype = b.type;

        if( atype == "dir" && btype != "dir"){
            return -1;
        } else if( atype != "dir" && btype == "dir" ){
            return 1;
        } else {
            if( String( a.name).toLowerCase() < String( b.name).toLowerCase() ) return -1;
            if( String( a.name).toLowerCase() > String( b.name).toLowerCase()) return 1;
            return 0;
        }
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

            // Sort the content so that the folders are on top
            content.sort( self._sortingFunction );

            for( var i = 0, length = content.length; i < length; i++ ){

                var file = $.extend({}, content[i]);
                file.size = this._filesizeFormat( file.size );
                var filerow = this._formatFilerow( file );

                var rowelement = $(filerow)
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

            }
        }
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