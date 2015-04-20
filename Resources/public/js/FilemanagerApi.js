"use strict";

var FilemanagerAPI = function( options) {
    var defaults = {
        debug: false,
        api: {
            url: location.protocol + '//' + location.host + location.pathname,
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
                if( typeof config.api.url !== 'undefined'){
                    this._url = config.api.url;
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

        this.debug( "Initializing" );
        this.debug( config );

        return this;
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

        return $.ajax({
            url: path,
            data: parameters,
            dataType: "json",
            method: method,
            self: self,
            beforeSend: function (jqXHR) {
                this.self.debug("Sending " + this.method + " request to " + this.url + "...");
            }

            // Log the response
        }).done( function( data ){
            this.self.debug( data );

            // Log the failure
        }).fail( function( jqXHR, error, errorMessage ){
            this.self.debug( { statuscode: jqXHR.status,
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
            .done(function( data, status, jqXHR) {
                var contents = data.data.contents;

                this.self._eventHandler.trigger('filemanager:add_and_open', {contents: contents, directory: directory });
            });
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
            .done(function( data, status, jqXHR) {

            })
            .fail(function( data, status, jqXHR ) {

            });
    },

    /**
     * Send a move request to the server
     *
     * @param file                          The path to the file including the filename
     * @param new_location                  The new location of the file
     */
    move: function( file, new_location ){
        var url = this._url + this._path_move;

        this._sendRequest( url, "POST", { file: file, location: new_location } )
            .done(function( data, status, jqXHR) {

            })
            .fail(function( data, status, jqXHR ) {

            });
    },

    /**
     * Send a rename request to the server
     *
     * @param file                          The path to the file including the filename
     * @param new_filename                  The new location of the file
     */
    rename: function( file, new_filename ){
        var url = this._url + this._path_rename;

        this._sendRequest( url, "POST", { file: file, name: new_filename } )
            .done(function( data, status, jqXHR) {

            })
            .fail(function( data, status, jqXHR ) {

            });
    },

    /**
     * Send a request to the server that creates a directory
     *
     * @param path                          The path to the directory
     * @param name                          The name of the directory to be made
     */
    createDirectory: function( path, name ){
        var url = this._url + this._path_create;

        this._sendRequest( url, "POST", { type: "directory", location: path, name: name } )
            .done(function( data, status, jqXHR) {

            })
            .fail(function( data, status, jqXHR ) {

            });
    },

    /**
     * Send a request to the server that deletes a file or a directory
     *
     * @param path                          The path to the directory
     * @param name                          The name of the file or directory to destroy
     */
    delete: function( path, name ){
        var url = this._url + this._path_delete;

        this._sendRequest( url, "POST", { location: path, name: name } )
            .done(function( data, status, jqXHR) {

            })
            .fail(function( data, status, jqXHR ) {

            });
    }
};