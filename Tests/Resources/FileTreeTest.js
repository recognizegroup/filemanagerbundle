'use strict';

var filetree_filterUnnecesaryElements = function( treenodes, withtree ){
    var treedata = {};
    for(var folder_name in treenodes) {
        var node = treenodes[ folder_name ];

        var treenode = { "name": node.name, path: node.path, children: {} };

        // Add the children
        if( typeof node.children === "object" && node.children !== null ){

            var keys = [];
            for( var key in node.children ){
                keys.push( key );
            }
            if( keys.length > 0 ){
                treenode.children = filetree_filterUnnecesaryElements( node.children, false );
            }
        }
        treedata[ node.name ] = treenode;
    }

    if( withtree == true ){
        treedata = {children: treedata};
    }

    return treedata;
};

describe('FileTree', function() {
    var tree;
    beforeEach(function() {

        tree = new FileTree( {
            eventHandler: {
                trigger: function(){},
                register: function(){}
            }
        } );
    });

    it('should be able to add a single file to the root', function() {
        tree.addFiles([ { path: "one", directory: "", name: "one" } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    directory: "",
                    name: "one",
                    children: {}
                }
            }
        };

        expect( tree._root ).toEqual ( expectedRoot );
    });

    it('should be able to add a single file to the root through the addChanges method', function() {
        tree.addChanges([ { type:"create", file: { path: "one", directory:"", name: "one" } } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    directory: "",
                    name: "one",
                    children: {}
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to add a nested node to the directory that exists in the root', function() {
        tree.addFiles([ { directory: "", path: "one", name: "one" }, { path: "one/two", directory: "one", name: "two"}]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    directory: "",
                    name: "one",
                    children: {
                        two: {
                            directory: "one/",
                            path: "one/two",
                            name: "two",
                            children: {}
                        }
                    }
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to add a file to the directory even if the parent directory doesn\'t exist yet', function() {
        tree.addFiles([ { path: "one/two/three/four", directory: "one/two/three/", name: "four" } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            directory: "one/",
                            name: "two",
                            children: {
                                three: {
                                    path: "one/two/three",
                                    directory: "one/two/",
                                    name: "three",
                                    children: {
                                        four: {
                                            path: "one/two/three/four",
                                            directory: "one/two/three/",
                                            name: "four",
                                            children: {}
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };

        expect( tree._root ).toEqual ( expectedRoot );
    });

    it('should be able to delete a file and its children from the tree', function() {
        tree._root = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {
                        two: {
                            path: "one",
                            name: "two",
                            children: {}
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "delete", file: { directory: "", name: "one" }, updatedfile: { } } ]);
        var expectedRoot = {
            children: {

            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to update a file node\'s contents', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            directory: "one/",
                            name: "two",
                            children: {}
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "update", file: { directory: "", name: "one" }, updatedfile: { directory: "", name: "one", extraProperty: "yo" } } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {}
                        }
                    },
                    extraProperty: "yo"
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to rename a file node and update its child paths', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {
                                three: {
                                    path: "one/two/three",
                                    name: "three",
                                    directory: "one/two/",
                                    children: {}
                                }
                            }
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "rename", file: { directory: "", name: "one" }, updatedfile: { directory: "", name: "newone" } } ]);
        var expectedRoot = {
            children: {
                newone: {
                    path: "newone",
                    name: "newone",
                    directory: "",
                    children: {
                        two: {
                            path: "newone/two",
                            directory: "newone/",
                            name: "two",
                            children: {
                                three: {
                                    path: "newone/two/three",
                                    name: "three",
                                    directory: "newone/two/",
                                    children: {}
                                }
                            }
                        }
                    }
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to move a file node and update its child paths', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {
                                three: {
                                    path: "one/two/three",
                                    directory: "one/two/",
                                    name: "three",
                                    children: {}
                                }
                            }
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "move", file: { directory: "one/", name: "two" }, updatedfile: { directory: "", name: "two" } } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "one",
                    directory: "",
                    name: "one",
                    children: {

                    }
                },
                two: {
                    path: "two",
                    directory: "",
                    name: "two",
                    children: {
                        three: {
                            path: "two/three",
                            directory: "two/",
                            name: "three",
                            children: {}
                        }
                    }
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to open a directory and get its immediate children', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {}
                        }
                    }
                }
            }
        };

        tree.openPath( "one" );

        expect( tree._currentPath).toEqual ( "one" );
        expect( tree._currentFiles).toEqual( [ { path: "one/two", directory: "one/", name: "two", children: {} } ] );
    });

    it('should be able to open the root folder and get its immediate children', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {}
                        }
                    }
                }
            }
        };

        tree.openPath( "" );
        expect( tree._currentPath).toEqual ( "" );
        expect( tree._currentFiles).toEqual( [ { path: 'one', name: 'one', directory: "", children: { two: { path: 'one/two', directory: "one/", name: 'two', children: {  } } } } ] );
    });

    it('shouldn\'t get any children if the path doesn\'t exist in the filetree', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {}
                        }
                    }
                }
            }
        };

        tree.openPath( "nonexistingdir" );

        expect( tree._currentFiles).toEqual( [] );
    });

    it('should be able to turn the tree data into data for jstree', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            children: {}
                        }
                    }
                }
            }
        };

        var expectedtree = [
            {
                text: "one",
                li_attr: {
                    id: "js_tree_file_1",
                    "data-path": "one",
                    "data-synchronized": true
                },
                state: {
                    opened: false,
                    selected: false,
                    disabled: false
                },
                children: [
                    {
                        text: "two",
                        li_attr: {
                            id: "js_tree_file_2",
                            "data-path": "one/two",
                            "data-synchronized": true
                        },
                        state: {
                            opened: false,
                            selected: false,
                            disabled: false
                        }
                    }
                ]
            }
        ];

        expect( tree.toJstreeData() ).toEqual( expectedtree );
    });

    it('should be able to open the jstree directory recursively on load', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: ""
                }
            }
        };

        tree.addFiles([{ name: "two", directory:"one/", path: "one/two" },
            { name: "three", path: "one/two/three", directory: "one/two/" },
            { name: "four", path: "one/two/three/four", directory: "one/two/three/" }]);
        tree.openPath("one/two");

        var expectedtree = [
            {
                text: "one",
                li_attr: {
                    id: "js_tree_file_1",
                    "data-path": "one",
                    "data-synchronized": true
                },
                state: {
                    opened: true,
                    selected: false,
                    disabled: false
                },
                children: [
                    {
                        text: "two",
                        li_attr: {
                            id: "js_tree_file_2",
                            "data-path": "one/two",
                            "data-synchronized": true
                        },
                        state: {
                            opened: true,
                            selected: false,
                            disabled: false
                        },
                        children:[
                            {
                                text: "three",
                                li_attr: {
                                    id: "js_tree_file_3",
                                    "data-path": "one/two/three",
                                    "data-synchronized": true
                                },
                                state: {
                                    opened: false,
                                    selected: false,
                                    disabled: false
                                },
                                children: [
                                    {
                                        text: "four",
                                        li_attr: {
                                            id: "js_tree_file_4",
                                            "data-path": "one/two/three/four",
                                            "data-synchronized": true
                                        },
                                        state: {
                                            opened: false,
                                            selected: false,
                                            disabled: false
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ];

        expect( tree.toJstreeData() ).toEqual( expectedtree );
    });

    it('should be able to output only directories for jstree', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            type: "file",
                            children: {}
                        }
                    }
                }
            }
        };

        var expectedtree = [
            {
                text: "one",
                li_attr: {
                    id: "js_tree_file_1",
                    "data-path": "one",
                    "data-synchronized": true
                },
                state: {
                    opened: false,
                    selected: false,
                    disabled: false
                },
                children: [ ]
            }
        ];

        expect( tree.toJstreeData( tree._root.children, true ) ).toEqual( expectedtree );
    });

    it('should be able to disable file nodes when outputting for jstree', function() {
        tree._root = {
            children: {
                one: {
                    path: "one",
                    name: "one",
                    directory: "",
                    children: {
                        two: {
                            path: "one/two",
                            name: "two",
                            directory: "one/",
                            type: "file",
                            disabled: true,
                            children: {}
                        }
                    }
                }
            }
        };

        var expectedtree = [
            {
                text: "one",
                li_attr: {
                    id: "js_tree_file_1",
                    "data-path": "one",
                    "data-synchronized": true
                },
                state: {
                    opened: false,
                    selected: false,
                    disabled: false
                },
                children: [
                    {
                        text: "two",
                        li_attr: {
                            id: "js_tree_file_2",
                            "data-path": "one/two",
                            "data-synchronized": true
                        },
                        state: {
                            opened: false,
                            selected: false,
                            disabled: true
                        }
                    }
                ]
            }
        ];

        expect( tree.toJstreeData( ) ).toEqual( expectedtree );
    });

    it('should be able to sort the current files in a normal filemanager manner', function() {
        tree._currentFiles = [{
            path: "b",
            name: "b",
            directory: "",
            type: "dir"
        },{
            path: "a/a",
            name: "a",
            directory: "",
            type: "file"
        },{
            path: "a",
            name: "a",
            directory: "",
            type: "dir"
        }];

        tree._updateViews( false, true, false );
        var expectedContent = [{
            path: "a",
            name: "a",
            directory: "",
            type: "dir"
        },{
            path: "b",
            name: "b",
            directory: "",
            type: "dir"
        },{
            path: "a/a",
            name: "a",
            directory: "",
            type: "file"
        }];

        expect( tree._currentFiles ).toEqual( expectedContent );
    });

    it('should be able to set a custom sort to the current files', function() {
        tree._currentFiles = [{
            path: "two",
            name: "two",
            directory: ""
        },{
            path: "two/one",
            name: "one",
            directory: "two/"
        }];

        tree.sortContent( function( a, b ){ return -1; });
        var expectedContent = [{
            path: "two/one",
            name: "one",
            directory: "two/"
        },{
            path: "two",
            name: "two",
            directory: ""
        }];

        expect( tree._currentFiles ).toEqual ( expectedContent );
    });

    it('should be able to reset the sorting function', function() {
        tree._currentFiles = [{
            path: "b",
            name: "b",
            directory: "",
            type: "dir"
        }, {
            path: "a",
            name: "a",
            directory: "",
            type: "dir"
        },{
            path: "a/a",
            name: "a",
            directory: "",
            type: "file"
        }];

        tree.sortContent( function(a, b ){ return -1; });
        tree.resetSort();
        var expectedContent = [{
            path: "a",
            name: "a",
            directory: "",
            type: "dir"
        },{
            path: "b",
            name: "b",
            directory: "",
            type: "dir"
        },{
            path: "a/a",
            name: "a",
            directory: "",
            type: "file"
        }];

        expect( tree._currentFiles ).toEqual ( expectedContent );
    });

});

