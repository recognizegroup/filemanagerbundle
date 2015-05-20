(function() {

    $.fn.filemanager = function( config ){
        if( typeof config === "undefined"){
            config = {};
        }
        config.eventHandler = new FilemanagerEventHandler( config );

        if( config.api !== null && typeof config.api === 'object' ) {
            if (typeof config.api.url !== 'undefined' && config.api.url != "") {
                this.data("event", config.eventHandler );
                this.data('view', new FileTreeView( config ) );
                this.data('api', new FilemanagerAPI( config ) );
                this.data('tree', new FileTree( config ) );
            } else {
                console.error("Filemanager: NO APILINK FOUND - Aborting creation");
            }
        }

        var self = this;
        var methods = {

            refresh: function(){
                self.data("tree").refresh();
                return self;
            },

            moveUpDirectory: function(){
                self.data("tree").moveUpDirectory();
            },

            createDirectory: function(){
                var path = self.data("tree").getCurrentPath();
                self.data("view")._createDirectory( path );

                return self;
            },

            search: function( value ){
                self.data("tree").search( value );
                return self;
            },

            on: function( eventstring, listener ){
                self.data("event").register(eventstring, listener);
                return self;
            }
        };

        this.data("filemanager", methods);
        return this;
    };

})( jQuery );