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
    _keepFocusOnDirectories: false,
    _keepFocusOnContent: false,

    _uploadFunctionality: null,
    _currentDirectory: "",

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
        this._directoryElement = $( config.elements.directories );
        this._titlebarElement = $( config.elements.title );
        this._contentElement = $( config.elements.main );

        this._eventHandler = config.eventHandler;

        this._contentElement.on("dragover", function( event ){
            self._contentElement.css("background", "red");
        }).on("dragleave", function( event ){
            self._contentElement.css("background", "white");
        });

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

        if( typeof config.uploadFormat === "function" ){
            this._formatUpload = config.uploadFormat;
        }

        if( typeof config.createdirectoryFormat === "function" ){
            this._formatCreatedirectoryButton = config.createdirectoryFormat;
        }

        this._addFunctionality();
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
     * Format the uploading area of the filemanager
     *
     * @param action                        The link to which the upload should go to
     * @param buttonid                      The id which links to the button that can be pressed
     * @param uploadname                    The fieldname of the file upload field
     * @param directoryname                 The fieldname of the directory selection field
     * @param current_directory             The current directory
     * @returns {string}
     * @private
     */
    _formatUpload: function( action, buttonid, uploadname, directoryname, current_directory){
        return '<form enctype="multipart/form-data" method="POST" action="' + action + '">' +
            '<input type="hidden" name="' + directoryname + '" value="' + current_directory + '" />' +
            '<input type="file" name="' + uploadname + '"/>' +
            '<input type="submit" id="' + buttonid + '" name="Uploaden" />' +
            '</form>';
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

        this._currentDirectory = current_directory;
        $("[data-fm-value=current_directory]").text( "/" + current_directory );

        if( this._titlebarElement.length > 0 ){
            this._titlebarElement.empty();

            // Add the uploading button
            var uploadstring = this._formatUpload( "/admin/fileapi/create", "uploadbutton",
                "filemanager_upload", "filemanager_directory", current_directory );
            var uploadelement = $( uploadstring );
            uploadelement.addClass('upload-container');
            uploadelement.appendTo( this._titlebarElement );

            // Add the hidden progress row
            var progressstring = '<div class="col-xs-9"><div id="progressOuter" class="progress progress-striped active" style="display:none;">' +
                '<div id="progressBar" class="progress-bar progress-bar-success"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div></div></div>';
            $(progressstring).appendTo( this._titlebarElement );
            var progressOuter = $("#progressOuter");
            var progressBar = $("#progressBar");

            // Add the AJAX upload functionality
            var uploader = new ss.SimpleUpload({
                url: "/admin/fileapi/create",
                name: "filemanager_upload",
                method: 'POST',
                hoverClass: 'focus',
                focusClass: 'active',
                multipart: true,
                data: {
                    filemanager_directory: current_directory
                },
                responseType: 'json',
                dropzone: "filemanager_view",
                debug: self._debug,
                startXHR: function() {
                    progressOuter.css('display', 'block'); // make progress bar visible
                    this.setProgressBar( progressBar );

                    self._eventHandler.trigger("filemanager:api:loading");
                },

                onComplete: function(filename, response){
                    self._eventHandler.trigger('filemanager:view:ajax_upload', {response: response, directory: current_directory } );
                },

                onError: function( filename, errorType, status, statusText, response ){
                    progressOuter.css('display', 'none');
                    self._eventHandler.trigger("filemanager:api:done");

                    response = JSON.parse( response );
                    if( response != false ){
                        self._eventHandler.trigger('filemanager:api:error', {message: response.data.message, status: statusText, statuscode: status });
                    }
                }
            });

            // Prevent multiple upload screens from showing
            if( this._uploadFunctionality != null ){
                this._uploadFunctionality.destroy();
            }
            this._uploadFunctionality = uploader;

            // Ajax upload button
            var uploadbutton = uploadelement.find('#uploadbutton');
            uploadbutton.off("click").off("keydown").off("keyup");
            if( uploadbutton.length == 0 ){
                this.errorLog("Upload ID wasn't set in the upload element");
            }

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

                            // Keep the focus on the content items if we are using a keyboard
                            self._keepFocusOnContent = true;

                            self._openContentEvent( event );
                        }

                    }).on('dblclick', { file: file }, function( evt ){
                        self._openContentEvent( evt );
                    });

                self._addContextmenuToRow( "." + rowclass, file );

                // Ensure we keep focus on the content area
                if( i == 0 && self._keepFocusOnContent == true ){
                    rowelement.focus();
                    self._keepFocusOnContent = false;
                }
            }
        }

        self._eventHandler.trigger("filemanager:view:rendered");
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
                    case "delete":
                        self._eventHandler.trigger('filemanager:view:delete', { file: options.file });
                        break;
                }
            },
            items: {
                "rename": {name: "Rename", icon: "edit"},
                "delete": {name: "Delete", icon: "delete"}
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
        var renameelement = $( self._formatRenamerow( copiedfile ) );
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
     * Add listeners and functionality to the elements designated to have this functionality
     *
     * Possible functionalities
     *
     * search - The search input field
     * directory_up - Button that moves up a directory
     * refresh - Refreshes the current directory
     * create_directory - Button that creates a directory row in the filemanager
     * grid_view - Set the content view as grid
     * list_view - Set the content view as a list
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