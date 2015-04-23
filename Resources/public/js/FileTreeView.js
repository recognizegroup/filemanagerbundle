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
     * Destroy and recreate the titlebar view
     */
    refreshTitlebar: function( current_directory ){
        var self = this;

        this.debug("Refreshing titlebar with directory " + current_directory );

        if( this._titlebarElement.length > 0 ){
            this._titlebarElement.empty();

            var directorystring = '<p class="topdir">' + current_directory + '</p>';
            var directoryelement = $( directorystring );
            directoryelement.appendTo( this._titlebarElement );

            var searchstring = '<input type="search" name="filemanager_search" />';
            var searchelement = $( searchstring );
            searchelement.appendTo( this._titlebarElement )
                .on('search', { directory: current_directory }, function( event ){
                    self._searchEvent( event );
                }).on('keyup', { directory: current_directory }, function( event ){

                    // Search on Enter
                    if( event.keyCode == 13 ){
                        self._searchEvent( event );
                    }
                });
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
     * Destroy and recreate the main file views
     *
     * @param content
     */
    refreshContent: function( content ){
        var self = this;
        if( this._contentElement.length > 0 ){

            // Clear the view before filling it
            this._contentElement.empty();


            for( var i = 0, length = content.length; i < length; i++ ){
                var file = content[i];
                var filerow = this._formatFilerow( file );

                var rowelement = $(filerow)
                    .appendTo( this._contentElement )
                    .on('click',{ file: file }, function( evt ){
                        $( evt.currentTarget).toggleClass('selected');


                    }).on('dblclick', { file: file }, function( evt ){

                        var directory = evt.data.file.directory;
                        var path = evt.data.file.path;
                        if( typeof evt.data.file.type == "undefined" || evt.data.file.type == "dir" ) {
                            var synchronized = false;
                            self._eventHandler.trigger('filemanager:view:open', {directory: path, isSynchronized: synchronized} );
                        } else {
                            self._eventHandler.trigger('filemanager:view:select', { file: path });
                        }
                    });
            }
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