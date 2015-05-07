(function() {
    $.fn.filemanager = function( config ) {
        if( typeof config === "undefined"){
            config = {};
        }
        config.eventHandler = new FilemanagerEventHandler( config );

        if( config.api !== null && typeof config.api === 'object' ) {
            if (typeof config.api.url !== 'undefined' && config.api.url != "") {
                this.data('treeview', new FileTreeView( config ) );
                this.data('api', new FilemanagerAPI( config ) );
                this.data('tree', new FileTree( config ) );
            } else {
                console.error("Filemanager: NO APILINK FOUND - Aborting creation");
            }
        }

        return this;
    };

})( jQuery );