"use_strict";

var FileTree = function(options){
    var defaults = {
        debug: false
    };
    this.options = $.extend(true, defaults, options);
    this.init( this.options );
};

/**
 * Manages the in-memory file tree
 */
FileTree.prototype = {
    _debug: false,
    _root: {
        children: {}
    },
    _currentPath: "",
    _currentFiles: [],
    _currentContentSort: null,
    _reverse: false,

    /** Used for setting the ID of the jstree */
    _walk_itteration: 0,

    _eventHandler: null,

    /**
     * Initialize the Tree configuration
     */
    init: function (config ) {

        if( config !== null && typeof config === 'object' ){
            this._debug = config.debug;
            this._eventHandler = config.eventHandler;

            this._registerEvents();
        }

        this._currentContentSort = this._naturalFilemanagerSort;

        this.debug( "Initializing" );
        this.debug( config );

        return this;
    },

    /**
     * Add new files to the FileTree and check for conflicts
     *
     * @param files                 An array containing file objects
     * @param shouldUpdateView      Boolean: whether to update the view or not after this action
     */
    addFiles: function( files, shouldUpdateView ){
        for( var i = 0, length = files.length; i < length; i++ ){
            var file = files[i];
            this._setFile( file );
        }

        this.debug( "Tree structure after updating" );
        this.debug( this._root );

        if( shouldUpdateView == true ){
            this._updateViews( true, false, false );
        }
    },

    /**
     * Set the current contents visible in the mainview
     *
     * @param files
     */
    setContents: function( files ){
        var formattedfiles = [];

        for( var i = 0, length = files.length; i < length; i++ ){
            var filenode = files[i];

            // Format the path correctly
            filenode.path = this._formatPath( filenode.path, true );
            filenode.directory = this._formatPath( filenode.directory );
            formattedfiles.push( filenode );
        }

        this._currentFiles = formattedfiles;
    },

    /**
     * Add or override a filenode
     */
    _setFile: function( filenode ){

        // Add the full path to the file if doesn't exist
        if( filenode.hasOwnProperty('path') == false ){
            filenode.path = this._formatPath( filenode.directory ) + filenode.name;
        }

        // Format the path correctly
        filenode.path = this._formatPath( filenode.path, true );
        filenode.directory = this._formatPath( filenode.directory );

        // Make sure the children property exists
        if( filenode.hasOwnProperty('children') == false ){
            filenode.children = {};
        }

        var node = this._findNode( filenode.directory );

        // Just set the value if the childnode doesn't exist
        if( typeof node.children[ filenode.name ] === "undefined" ){
            node.children[ filenode.name ] = filenode;

            // Otherwise recursively merge the values of the node
        } else {
            node.children[ filenode.name] = $.extend( true, node.children[ filenode.name ], filenode );
        }
    },

    /**
     * Walk through the tree - Adding nodes where they aren't set,
     * before returning the node object that was found
     *
     * @return object
     */
    _findNode: function( path ){
        var pathnodes = this._extractPathNodes( path );

        // Get the file node in the nested structure
        var node = this._root;
        for( var i = 0, length = pathnodes.length; i < length; i++ ){
            var pathnode = pathnodes[ i ];

            if( typeof node.children[ pathnode ] === "undefined") {
                node.children[ pathnode ] = this._generateNode( pathnode, pathnodes );
            }

            node = node.children[ pathnode ];
        }

        if( typeof node.children === "undefined"){
            node.children = {};
        }
        return node;
    },

    /**
     * Generate a node from a path - Still needs to be synchronized if more data of this node is needed
     *
     * @param nodename              The node to end on
     * @param pathnodes             The pagenodes of the object
     * @returns {*}                 Node object
     * @private
     */
    _generateNode: function( nodename, pathnodes ){
        var node = {};
        node.name = nodename;

        // Build the path
        var newpath = "";
        for( var i = 0; i < pathnodes.length; i++ ){
            newpath += pathnodes[ i ] + "/";

            if( pathnodes[i + 1] == nodename ){
                break;
            }
        }

        node.directory = newpath;
        node.path = newpath + nodename;
        node.children = {};

        return node;
    },

    /**
     * Extract the actual path nodes without empty strings caused by multiple backslashes
     *
     * @return []
     */
    _extractPathNodes: function( path ){
        if( typeof path !== 'string' ){
            path = "";
        }

        var crudepathnodes = path.split('/');
        var pathnodes = [];
        for( var i = 0, length = crudepathnodes.length; i < length; i++ ){
            if( crudepathnodes[i] !== "" ){
                pathnodes.push( crudepathnodes[i] );
            }
        }

        return pathnodes;
    },

    /**
     * Add changes to the FileTree like:
     *      Creating a filenode
     *      Moving treenodes around or renaming a node
     *      Deleting a node and its children
     *
     * @param changes               An array containing changes
     * @param shouldUpdateView      Boolean: whether to update the view or not after this action
     */
    addChanges: function( changes, shouldUpdateView ){

        // Loop through the changes and execute them
        if( typeof changes !== "undefined" || !changes ){
            for( var i = 0, length = changes.length; i < length; i++ ){
                this._executeChange( changes[ i ] );
            }
        }

        this.debug( "Tree structure after updating" );
        this.debug( this._root );

        this.debug( "Updating the current files" );
        this.openPath( this._currentPath, false );
        this.debug( this._currentFiles );
        if( shouldUpdateView ){
            this._updateViews( true, true, true );
        }
    },

    /**
     * Route the different types of changes to their correct execution methods
     *
     * @param change            A change object containing a type, the current file and the updated file data
     */
    _executeChange: function( change ){
        switch( change.type ){
            case "delete":
                this._deleteNode( change.file );
                break;
            case "create":
                this._setFile( change.file );
                break;
            case "update":
            case "rename":
            case "move":
                this._updateNode( change.file, change.updatedfile );
                break;
        }
    },

    /**
     * Update the node and where needed its children
     * If the path has changed, destroy the old file node as well
     *
     * @param file          The old file
     * @param newfile       The updated file data
     */
    _updateNode: function( file, newfile ){
        var node = this._findNodeIfExists( file.directory );

        if( node !== false || typeof node.children[ file.name ] !== "undefined" ){
            var currentfile = node.children[ file.name ];
            var oldpath = this._formatPath( currentfile.path );

            newfile.path = newfile.directory + newfile.name;
            var updatedfile = $.extend( true, currentfile, newfile );
            var newpath = this._formatPath( newfile.path );


            // Check if the path location has changed
            if( oldpath != newpath ){
                updatedfile = this._updateChildPaths( updatedfile );

                // Delete the old file
                this._deleteNode( file );
            }

            this._setFile( updatedfile );
        }
    },

    /**
     * Recursively update the paths of the child nodes
     *
     * @return object             The updated node
     */
    _updateChildPaths: function( node ){
        if( typeof node.children !== "undefined" ){
            var keys = [];
            for( var key in node.children ){
                keys.push( key );
            }

            // Loop through the children and update the paths
            for( var i = 0, length = keys.length; i < length; i++ ){
                if( typeof node.children[ keys[ i ] ] !== "undefined" ){
                    var childnode = node.children[ keys[ i ] ];

                    childnode.directory = this._formatPath( node.path );
                    childnode.path = this._formatPath( node.path ) + childnode.name;

                    node.children[ keys[ i ] ] = this._updateChildPaths( childnode );
                }
            }
        }

        return node;
    },

    /**
     * Format a path to the correct format
     *
     * @return string
     */
    _formatPath: function( path, withouttrailingslash ){
        if( typeof path === "undefined"){
           path = "";
        }

        // Remove the first slash
        if( path.length !== 0 && path.substr( 0, 1 ) == "/") {
            path = path.substr( 1, path.length - 1 );
        }

        // Append a new slash
        if( withouttrailingslash !== true && path.length !== 0 && path.substr( path, path.length - 1 ) != "/"){
            path += "/";
        }

        // Replace slash duplicates with a single slash
        path = path.replace(/\/\/+/g, '/');

        return path;
    },

    /**
     * Delete a node and its children if it exists
     *
     * @param file              A filenode containing a path and a name
     */
    _deleteNode: function( file ){
        var parent = this._findNodeIfExists( file.directory );
        if( parent !== false ){
            delete parent.children[ file.name ];
        }
    },

    /**
     * Walk through the tree and find the node if it exists
     * Returning the node object that was found or an empty object if nothing was found
     *
     * @return object
     */
    _findNodeIfExists: function( path ){
        var pathnodes = this._extractPathNodes( path );

        // Get the file node in the nested structure
        var node = this._root;
        for( var i = 0, length = pathnodes.length; i < length; i++ ){
            var pathnode = pathnodes[ i ];

            if( typeof node.children[ pathnode ] === "undefined") {
                node = false;
                break;
            }

            node = node.children[ pathnode ];
        }

        return node;
    },

    /**
     * Turns the current in memory file tree into a list of files that the jsTree plugin can understand
     *
     * @param directoriesOnly           Whether to add files or just directories
     */
    flattenTree: function( directoriesOnly ){
        var jstreedata = this.toJstreeData(this._root.children, directoriesOnly );
        this._walk_itteration = 0;

        this.debug( "JsTree data updated" );
        this.debug( jstreedata );

        return jstreedata;
    },

    /**
     * Recursively walk over the entire tree and call a function for each file
     *
     * @param nodes                     The filenodes to parse
     * @param directoriesOnly           Whether to add files or just directories
     */
    toJstreeData: function( nodes, directoriesOnly ){
        if( typeof nodes === "undefined" ){
            nodes = this._root.children;
        }

        var jstreedata = [];
        for(var folder_name in nodes) {
            var node = nodes[ folder_name ];

            // Make sure files are filtered out if we only want directories
            if( !directoriesOnly ||
                ( directoriesOnly === true && ( typeof node.type === "undefined" || node.type === "dir" ) ) ){
                this._walk_itteration++;

                var jstreenode = { "text": node.name };
                jstreenode.li_attr = {
                    id: "js_tree_file_" + this._walk_itteration,
                    "data-path": node.path,

                    // Because we generate nodes based on the path if they don't exist,
                    // We need to add a synchronized tag to make sure data gets retrieved from the server
                    "data-synchronized": typeof node._generated === "undefined"
                };

                // Add the different states
                jstreenode.state = {
                    opened: this._isOpenedDirectory( node ),
                    selected: false,
                    disabled: ( typeof node.disabled !== "undefined" && node.disabled == true )
                };

                // Add the children
                if( typeof node.children === "object" && node.children !== null ){
                    var keys = [];
                    for( var key in node.children ){
                        keys.push( key );
                    }
                    if( keys.length > 0 ){
                        jstreenode.children = this.toJstreeData( node.children, directoriesOnly )
                    }
                }
                jstreedata.push( jstreenode );
            }
        }

        return jstreedata;
    },

    /**
     * Open the path
     *
     * @param path
     * @param shouldUpdateView      Boolean: whether to update the view or not after this action
     */
    openPath: function( path, shouldUpdateView ){
        this._currentPath = path;

        var node = this._findNodeIfExists( path );
        if( node === false ){
            this._currentPath = false;
            this._currentFiles = [];

        } else {
            if( path !== "" && node == this._root){
                this._currentFiles = [];

            } else {
                var children = [];
                for( var property in node.children ){
                    children.push( node.children[ property ] );
                }

                this.setContents( children );
            }
        }

        if( shouldUpdateView ){
            this._updateViews( true, true, true );
        }
    },

    /**
     * Checks if the node should be opened by comparing the current path to the complete node path
     *
     * @param node              The node to check
     * @private
     */
    _isOpenedDirectory: function( node ){
        var currentPathNodes = this._extractPathNodes( this._currentPath );
        var checkedPathNodes = this._extractPathNodes( this._formatPath( node.path ) );

        var shouldBeOpened = false;
        for( var i = 0, length = checkedPathNodes.length; i < length; i++ ){
            shouldBeOpened = currentPathNodes[i] === checkedPathNodes[i];
        }

        return shouldBeOpened;
    },

    /**
     * The natural sorting function where folders are kept on top and the ordering is alphabetical
     *
     * @param a
     * @param b
     * @returns {number}
     * @private
     */
    _naturalFilemanagerSort: function( a, b ){
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
     * Sort the current files using the sort function
     * Also toggles the sorting if the same sort function is set
     *
     * @param sortfunction
     */
    sortContent: function( sortfunction ){
        if( typeof sortfunction !== "function" ) {
            sortfunction = this._naturalFilemanagerSort;
        }

        // Toggle the sorting order
        if( String(this._currentContentSort) == String(sortfunction) ){
            this._reverse = !this._reverse;
        } else {
            this._reverse = false;
        }
        this._currentContentSort = sortfunction;

        this._updateViews(false, true, false);

    },

    /**
     * Reset the sorting
     */
    resetSort: function(){
        this.sortContent( this._naturalFilemanagerSort );
    },

    /**
     * Update the views linked to the tree data
     *
     * @param directories               The list of directories
     * @param content                   The list of files and directories in the currently opened path
     * @param path                      The indicator where the user is now
     *
     * @private
     */
    _updateViews: function( directories, content, path ){
        if( directories == true ){
            this._eventHandler.trigger('filemanager:model:directories_changed', this.flattenTree( true ) );
        }

        if( content == true ){
            this._currentFiles.sort( this._currentContentSort );
            if( this._reverse ){
                this._currentFiles.reverse();
            }
            this._eventHandler.trigger('filemanager:model:content_changed', this._currentFiles );
        }

        if( path == true ){
            this._eventHandler.trigger('filemanager:model:path_changed', this._currentPath );
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
     * Displays debug data
     * @param debug_message
     */
    debug: function( debug_message ) {
        if( typeof debug_message === "string" ){
            debug_message =  "FileTree: " + debug_message;
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

        this._eventHandler.register('filemanager:api:add_data', function( eventobj ){
            self.addFiles( eventobj.contents );
            self.openPath( eventobj.directory, true );
        });

        this._eventHandler.register('filemanager:api:update_data', function( eventobj ){
            self.addChanges( eventobj.contents, true );
        });

        this._eventHandler.register('filemanager:api:search_data', function( eventobj ) {
            self.setContents(eventobj.contents);
            self._updateViews(false, true, false);
        });

        this._eventHandler.register('filemanager:view:directory_up', function( eventobj ){
            self.moveUpDirectory();
        });

        this._eventHandler.register("filemanager:view:sort", function( eventobj ){
            if( eventobj !== undefined && typeof eventobj.sortfunction === "function"){
                self.sortContent( eventobj.sortfunction );
            } else {
                self.sortContent();
            }
        });

        this._eventHandler.register('filemanager:view:open', function( eventobj ){

            // Only open the directory if the data already exists
            if( eventobj.isSynchronized ){
                self.openPath( eventobj.directory, true );
            }
        });

        this._eventHandler.register('filemanager:view:refresh', function( eventobj ){
            self.refresh();
        });

        this._eventHandler.register('filemanager:api:refresh', function( eventobj ){
            self.refresh();
        });
    },

    // --------------------- JQUERY FUNCTIONS

    refresh: function(){
        var self = this;
        this._eventHandler.trigger("filemanager:view:open", { directory: self._currentPath, isSynchronized: false } );
    },

    moveUpDirectory: function(){
        var self = this;
        this._eventHandler.trigger("filemanager:view:open", { directory: self._getHigherDirectory( self._currentPath ),
            isSynchronized: false } );
    },

    search: function( value ){
        var self = this;
        this._eventHandler.trigger("filemanager:view:search", { directory: self._currentPath, query: value } );
    },

    getCurrentPath: function(){
        return this._currentPath;
    }
};