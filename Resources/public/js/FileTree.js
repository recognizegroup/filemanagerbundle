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
            this._updateView();
        }
    },

    /**
     * Update the views linked to the tree data
     *
     * @private
     */
    _updateView: function(){
        this._eventHandler.trigger('filemanager:updateview', this.flattenTree() );
    },

    /**
     * Add or override a filenode
     */
    _setFile: function( filenode ){

        // Make sure the children property exists
        if( filenode.hasOwnProperty('children') == false ){
            filenode.children = {};
        }

        var node = this._findNode( filenode.path );

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
                var newnode = {};
                newnode.name = pathnode;

                // Build the path
                var newpath = "";
                for( var j = 0; j < i; j++ ){
                    newpath += pathnodes[ j ] + "/";
                }
                newnode.path = newpath;

                newnode.children = {};

                node.children[ pathnode ] = newnode;
            }

            node = node.children[ pathnode ];
        }

        if( typeof node.children === "undefined"){
            node.children = {};
        }
        return node;
    },

    /**
     * Extract the actual path nodes without empty strings caused by multiple backslashes
     *
     * @return []
     */
    _extractPathNodes: function( path ){
        if( path === false ){
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

        if( shouldUpdateView ){
            this._updateView();
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
        var node = this._findNodeIfExists( file.path );

        if( node !== false || typeof node.children[ file.name ] !== "undefined" ){
            var currentfile = node.children[ file.name ];
            var oldpath = this._appendSlashToPath( currentfile.path ) + currentfile.name;

            var updatedfile = $.extend( true, currentfile, newfile );
            var newpath = this._appendSlashToPath( newfile.path ) + newfile.name;


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
                    childnode.path = this._appendSlashToPath( node.path ) + node.name;

                    node.children[ keys[ i ] ] = this._updateChildPaths( childnode );
                }
            }
        }

        return node;
    },

    /**
     * Appends a slash to a path if the last character isn't a slash
     *
     * @return string
     */
    _appendSlashToPath: function( path ){
        if( typeof path === "undefined"){
           path = "";
        }

        if( path.length !== 0 && path.substr( path, path.length - 1 ) !== "/"){
            path += "/";
        }

        return path;
    },

    /**
     * Delete a node and its children if it exists
     *
     * @param file              A filenode containing a path and a name
     */
    _deleteNode: function( file ){
        var parent = this._findNodeIfExists( file.path );
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
                jstreenode.attr = { "id": "js_tree_file_" + this._walk_itteration };

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

                this._currentFiles = children;
            }
        }

        if( shouldUpdateView ){
            this._updateView();
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
        var checkedPathNodes = this._extractPathNodes( this._appendSlashToPath( node.path ) + node.name );

        var shouldBeOpened = false;
        for( var i = 0, length = checkedPathNodes.length; i < length; i++ ){
            shouldBeOpened = currentPathNodes[i] === checkedPathNodes[i];
        }

        return shouldBeOpened;
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

        this._eventHandler.register('filemanager:add_and_open', function( eventobj ){
            self.addFiles( eventobj.contents );
            self.openPath( eventobj.directory, true );
        });

    }
};