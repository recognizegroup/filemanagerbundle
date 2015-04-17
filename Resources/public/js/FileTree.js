"use_strict";

var FileTree = function(element, options){
    var defaults = {
        debug: false
    };
    this.options = $.extend(true, defaults, options);
    this.init( this.options, element );
};

/**
 * Manages the in-memory file tree
 */
FileTree.prototype = {
    _debug: false,
    _root: {
        children: {}
    },

    /** Used for setting the ID of the jstree */
    _walk_itteration: 0,

    /**
     * Initialize the Tree configuration
     */
    init: function (config, element) {
        this.$element = element;

        if( config !== null && typeof config === 'object' ){
            this._debug = config.debug;
        }

        this.debug( "Initializing" );
        this.debug( config );

        return this;
    },

    /**
     * Recursively walk over the entire tree and call a function for each file
     *
     * @param callback          The function that will be called on every file retrieved
     * @param nodes             File nodes
     */
    walk: function( callback, nodes ){
        if( typeof nodes === "undefined" ){
            nodes = this._root.children;
        }

        for(var folder_name in nodes) {
            var node = nodes[ folder_name ];
            this._walk_itteration++;

            callback( node, this._walk_itteration );
            if( typeof node.children === "object" && node.children !== null ){

                var keys = [];
                for( var key in node.children ){
                    keys.push( key );
                }

                if( keys.length > 0 ){
                    this.walk( callback, node.children );
                }
            }
        }
    },

    /**
     * Add new files to the FileTree and check for conflicts
     */
    addFiles: function( files ){
        for( var i = 0, length = files.length; i < length; i++ ){
            var file = files[i];
            this._setFile( file );
        }

        this.debug( "Tree structure after updating" );
        this.debug( this._root );

        this.updateJstree();
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
                node.children[ pathnode ] = { children: {} };
            }

            node = node.children[ pathnode ];
        }

        return node;
    },

    /**
     * Extract the actual path nodes without empty strings caused by multiple backslashes
     *
     * @return []
     */
    _extractPathNodes: function( path ){
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
     */
    addChanges: function( changes ){

        // Loop through the changes and execute them
        if( typeof changes !== "undefined" || !changes ){
            for( var i = 0, length = changes.length; i < length; i++ ){
                this._executeChange( changes[ i ] );
            }
        }

        this.debug( "Tree structure after updating" );
        this.debug( this._root );

        this.updateJstree();
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
     */
    flattenTree: function(){
        var jstreedata = [];

        this.walk(function(filenode, itteration){
            var parent = filenode.path;
            if( parent == "" ){
                parent = "#";
            }

            var jstreenode =  {"id": "js_tree_file_" + itteration, "text": filenode.name, "parent": parent };
            jstreedata.push( jstreenode );
        });

        this._walk_itteration = 0;

        return jstreedata;
    },

    /**
     * Update the state of the jsTree plugin
     */
    updateJstree: function(){
        this.$element.jstree('destroy').jstree({ 'core': {
            'data' : this.flattenTree(),
            'multiple': false}
        });
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
    }
};