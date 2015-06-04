(function() {

    $.fn.filemanager = function( config ){
        if( typeof config === "undefined"){
            config = {};
        }

        config.eventHandler = new FilemanagerEventHandler( config );

        if( config.api !== null && typeof config.api === 'object' ) {
            if (typeof config.api.url !== 'undefined' && config.api.url != "") {
                $.data(this.get(0), "event", config.eventHandler );
                $.data(this.get(0), 'view', new FileTreeView( config, this.get(0) ) );
                $.data(this.get(0), 'api', new FilemanagerAPI( config ) );
                $.data(this.get(0), 'tree', new FileTree( config ) );

                var self = this;
                var methods = {

                    refresh: function(){
                        $.data( self.get(0), "tree").refresh();
                        return self.eq(0);
                    },

                    moveUpDirectory: function(){
                        $.data( self.get(0), "tree").moveUpDirectory();
                    },

                    createDirectory: function(){
                        var path = self.data("tree").getCurrentPath();
                        $.data( self.eq(0), "view")._createDirectory( path );

                        return self.eq(0);
                    },

                    search: function( value ){
                        $.data( self.get(0), "tree").search( value );
                        return self.eq(0);
                    },

                    on: function( eventstring, listener ){
                        $.data( self.get(0), "event").register(eventstring, listener);
                        return self.eq(0);
                    }
                };

                $.data( this.get(0), "filemanager", methods);

            } else {
                console.error("Filemanager: NO APILINK FOUND - Aborting creation");
            }
        } else {
            console.error("Filemanager: NO APILINK FOUND - Aborting creation");
        }

        return this;
    };

})( jQuery );