"use strict";

var FilemanagerAPI = function( options) {
    var defaults = {
        debug: false,
        api: {
            url: window.location.href,
            paths: {
                create: "/create",
                read: "",
                search: "/search",
                move: "/move",
                rename: "/rename",
                delete: "/delete"
            }
        }
    };
    this.options = $.extend(true, defaults, options);
    this.init( this.options );
};

FilemanagerAPI.prototype = {

    _debug: false,
    _url: "",
    _path_create: "",
    _path_move: "",
    _path_rename: "",
    _path_read_directory: "",
    _path_delete: "",
    _path_search: "",
    _eventHandler: false,
    _disableRequests: false,

    /**
     * Initialize the API configuration
     */
    init: function (config) {
        if( config !== null && typeof config === 'object' ){
            this._eventHandler = config.eventHandler;

            if( typeof config.debug !== 'undefined'){
                this._debug = config.debug;
            }

            if( config.api !== null && typeof config.api === 'object' ){
                if( typeof config.api.url !== 'undefined' && config.api.url != ""){
                    this._url = config.api.url;
                } else {

                    this._disableRequests = true;
                    console.error( "NO APILINK FOUND !!!" );
                }

                if( config.api.paths !== null && typeof config.api.paths === 'object' ) {


                    if (typeof config.api.paths.move !== 'undefined') {
                        this._path_move = config.api.paths.move;
                    }

                    if (typeof config.api.paths.rename !== 'undefined') {
                        this._path_rename = config.api.paths.rename;
                    }

                    if (typeof config.api.paths.create !== 'undefined') {
                        this._path_create = config.api.paths.create;
                    }

                    if (typeof config.api.paths.delete !== 'undefined') {
                        this._path_delete = config.api.paths.delete;
                    }

                    if (typeof config.api.paths.read !== 'undefined') {
                        this._path_read_directory = config.api.paths.read;
                    }

                    if (typeof config.api.paths.search !== 'undefined') {
                        this._path_search = config.api.paths.search;
                    }
                }
            }

            if( typeof config.api.startingDirectory === 'string' ){
                this.read( config.api.startingDirectory );
            }
        }

        this._registerEvents();

        this.debug( "Initializing" );
        this.debug( config );

        return this;
    },

    /**
     * Send an ajax request to the server
     *
     * @param path                  The absolute link to the api
     * @param method                The request method
     * @param parameters            Parameters to be added to the URL
     *
     * @returns Promise object
     */
    _sendRequest: function( path, method, parameters ){
        if( typeof method === 'undefined'){
            method = "GET";
        }

        if( typeof parameters !== 'object' ){
            parameters = {};
        }

        // Add the reference to this object to the ajax settings
        // To allow for easy debug messages
        var self = this;

        this._eventHandler.trigger("filemanager:api:loading");

        return $.ajax({
            url: path,
            data: parameters,
            dataType: "json",
            method: method,
            self: self,
            beforeSend: function (jqXHR) {
                this.self.debug("Sending " + this.method + " request to " + this.url + "...");
            }

        }).done( function( data ){
            this.self._eventHandler.trigger("filemanager:api:done");

            // Log the response
            this.self.debug( data );

        }).fail( function( jqXHR, error, errorMessage ){
            this.self._eventHandler.trigger("filemanager:api:done");

            // Log the failure
            this.self.errorLog( { statuscode: jqXHR.status,
                statustext: jqXHR.statusText,
                response: jqXHR.responseText });
        });
    },

    /**
     * Send a read directory call to the server
     *
     * @param directory                     The directory to read
     */
    read: function( directory ){
        var url = this._url + this._path_read_directory;

        this._sendRequest( url, "GET", { directory: directory } )
            .success(function( data, status, jqXHR) {
                this.self._eventHandler.trigger('filemanager:api:add_data', {contents: data.data.contents, directory: directory });
            })
            .fail( this._handleApiError );
    },

    /**
     * Send a search call to the server
     *
     * @param directory                     The directory to read
     * @param query                         The value to search for
     */
    search: function( directory, query ){
        var url = this._url + this._path_search;

        this._sendRequest( url, "GET", { directory: directory, q: query } )
            .success(function( data, status, jqXHR) {
                this.self._eventHandler.trigger('filemanager:api:search_data', {contents: data.data.contents, directory: directory, query: query });
            })
            .fail( this._handleApiError );
    },

    /**
     * Send a move request to the server
     *
     * @param directory                     The directory from the file to move
     * @param filename                      The filename
     * @param new_location                  The new location of the file
     */
    move: function( directory, filename, new_location ){
        var url = this._url + this._path_move;

        var filepath = directory + filename;
        this._sendRequest( url, "POST", { filemanager_filepath: filepath, filemanager_newdirectory: new_location } )
            .success(function( data, status, jqXHR) {

                // Make sure our changes are in the correct format
                var changes = [];
                if( typeof data.data.changes == "object"){
                    for( var key in data.data.changes ){
                        changes.push( data.data.changes[key] );
                    }
                } else {
                    changes = data.data.changes;
                }

                // Update the current directory as well
                if( directory !== new_location ){
                    this.self._eventHandler.trigger('filemanager:api:refresh');
                }
                this.self._eventHandler.trigger('filemanager:api:update_data', {contents: changes, directory: new_location });
            })
            .fail( this._handleApiError );
    },

    /**
     * Send a rename request to the server
     *
     * @param directory                     The directory in which the file resides
     * @param filename                      The filename of the file to rename
     * @param new_filename                  The new location of the file
     */
    rename: function( directory, filename, new_filename ){
        var url = this._url + this._path_rename;

        this._sendRequest( url, "POST", { filemanager_directory: directory, filemanager_filename: filename, filemanager_newfilename: new_filename } )
            .success(function( data, status, jqXHR) {

                // Make sure our changes are in the correct format
                var changes = [];
                if( typeof data.data.changes == "object"){
                    for( var key in data.data.changes ){
                        changes.push( data.data.changes[key] );
                    }
                } else {
                    changes = data.data.changes;
                }

                this.self._eventHandler.trigger('filemanager:api:update_data', {contents: changes, directory: directory });
            })
            .fail( this._handleApiError );
    },

    /**
     * Send a request to the server that creates a directory
     *
     * @param path                          The path to the directory
     * @param name                          The name of the directory to be made
     */
    createDirectory: function( path, name ){
        var url = this._url + this._path_create;

        this._sendRequest( url, "POST", { type: "directory", filemanager_directory: path, directory_name: name } )
            .success(function( data, status, jqXHR) {

                // Make sure our changes are in the correct format
                var changes = [];
                if( typeof data.data.changes == "object"){
                    for( var key in data.data.changes ){
                        changes.push( data.data.changes[key] );
                    }
                } else {
                    changes = data.data.changes;
                }


                this.self._eventHandler.trigger('filemanager:api:update_data', {contents: changes, directory: path });
            })
            .fail( this._handleApiError );
    },

    /**
     * Send a request to the server that deletes a file or a directory
     *
     * @param path                          The path to the directory
     * @param name                          The name of the file or directory to destroy
     */
    delete: function( path, name ){
        var url = this._url + this._path_delete;

        this._sendRequest( url, "POST", { filemanager_directory: path, filemanager_filename: name } )
            .success(function( data, status, jqXHR) {

                // Make sure our changes are in the correct format
                var changes = [];
                if( typeof data.data.changes == "object"){
                    for( var key in data.data.changes ){
                        changes.push( data.data.changes[key] );
                    }
                } else {
                    changes = data.data.changes;
                }

                this.self._eventHandler.trigger('filemanager:api:update_data', {contents: changes, directory: path });
            })
            .fail( this._handleApiError );
    },

    /**
     * Handles the response of an AJAX upload
     *
     * @param directory             The directory to refresh
     * @param response              The JSON response
     */
    uploadResponse: function( directory, response ){
        this._eventHandler.trigger("filemanager:api:done");

        this._eventHandler.trigger('filemanager:api:update_data', {contents: response.data.changes, directory: directory});
    },

    /**
     * Handles an API error
     *
     * @param jqXHR
     * @private
     */
    _handleApiError: function( jqXHR ){
        var response = JSON.parse( jqXHR.responseText );
        this.self._eventHandler.trigger('filemanager:api:error', {message: response.data.message, status: response.status, statuscode: jqXHR.status });
    },

    /**
     * Displays debug data
     * @param debug_message
     */
    debug: function( debug_message ) {
        if( typeof debug_message === "string" ){
            debug_message =  "FilemanagerAPI: " + debug_message;
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
     * Register the events
     *
     * @private
     */
    _registerEvents: function(){
        var self = this;

        this._eventHandler.register('filemanager:view:open', function( eventobj ){
            if( eventobj.isSynchronized == false ){
                self.read( eventobj.directory );
            }
        });

        this._eventHandler.register('filemanager:view:search', function( eventobj ){
            self.search(eventobj.directory, eventobj.query );
        });

        this._eventHandler.register('filemanager:view:create', function( eventobj ){
            self.createDirectory( eventobj.directory, eventobj.name );
        });

        this._eventHandler.register('filemanager:view:rename', function( eventobj ){
            self.rename( eventobj.file.directory, eventobj.file.name, eventobj.newname );
        });

        this._eventHandler.register('filemanager:view:move', function( eventobj ){
            self.move( eventobj.file.directory, eventobj.file.name, eventobj.newlocation );
        });

        this._eventHandler.register('filemanager:view:delete', function( eventobj ){
            self.delete( eventobj.file.directory, eventobj.file.name );
        });

        this._eventHandler.register('filemanager:view:ajax_upload', function( eventobj ){
            self.uploadResponse( eventobj.directory, eventobj.response )
        });
    }
};