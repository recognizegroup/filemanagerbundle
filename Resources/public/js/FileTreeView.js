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

    _eventHandler: false,
    _container: false,

    _directoryElement: false,

    init: function( config, element ){
        this._container = element;
        this._directoryElement = $( config.elements.directories );

        this._eventHandler = config.eventHandler;

        this._registerEvents();
    },


    /**
     * Register the events
     *
     * @private
     */
    _registerEvents: function(){
        var self = this;

        this._eventHandler.register('filemanager:updateview', function( jstreedata ){

            // Update the view with the new data
            self._directoryElement.jstree('destroy').jstree({ 'core': {
                'data' : jstreedata,
                'multiple': false}
            });
        });
    }

};