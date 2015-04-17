'use strict';


describe('FileTree', function() {
    var tree;
    beforeEach(function() {
        var element = {
            jstree: function(){ return this }
        };

        tree = new FileTree( element, {} );
    });

    it('should be able to add a single file to the root', function() {
        tree.addFiles([ { path: "", name: "one" } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {}
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to add a single file to the root through the addChanges method', function() {
        tree.addChanges([ { type:"create", file: { path: "", name: "one" } } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {}
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to add a nested node to the directory that exists in the root', function() {
        tree.addFiles([ { path: "", name: "one" }, { path: "one", name: "two"} ]);
        var expectedRoot = {
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

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to add a file to the directory even if the parent directory doesn\'t exist yet', function() {
        tree.addFiles([ { path: "one/two", name: "three" } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {
                        two: {
                            path: "one",
                            name: "two",
                            children: {
                                three: {
                                    path: "one/two",
                                    name: "three",
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

        tree.addChanges([ { type: "delete", file: { path: "", name: "one" }, updatedfile: { } } ]);
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

        tree.addChanges([ { type: "update", file: { path: "", name: "one" }, updatedfile: { path: "", name: "one", extraProperty: "yo" } } ]);
        var expectedRoot = {
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
                    path: "",
                    name: "one",
                    children: {
                        two: {
                            path: "one",
                            name: "two",
                            children: {
                                three: {
                                    path: "one/two",
                                    name: "three",
                                    children: {}
                                }
                            }
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "rename", file: { path: "", name: "one" }, updatedfile: { path: "", name: "newone" } } ]);
        var expectedRoot = {
            children: {
                newone: {
                    path: "",
                    name: "newone",
                    children: {
                        two: {
                            path: "newone",
                            name: "two",
                            children: {
                                three: {
                                    path: "newone/two",
                                    name: "three",
                                    children: {}
                                }
                            }
                        }
                    },
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });

    it('should be able to move a file node and update its child paths', function() {
        tree._root = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {
                        two: {
                            path: "one",
                            name: "two",
                            children: {
                                three: {
                                    path: "one/two",
                                    name: "three",
                                    children: {}
                                }
                            }
                        }
                    }
                }
            }
        };

        tree.addChanges([ { type: "rename", file: { path: "one", name: "two" }, updatedfile: { path: "", name: "two" } } ]);
        var expectedRoot = {
            children: {
                one: {
                    path: "",
                    name: "one",
                    children: {

                    }
                },
                two: {
                    path: "",
                    name: "two",
                    children: {
                        three: {
                            path: "two",
                            name: "three",
                            children: {}
                        }
                    }
                }
            }
        };

        expect( tree._root).toEqual ( expectedRoot );
    });
});