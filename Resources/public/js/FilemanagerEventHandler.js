"use strict";

var FilemanagerEventHandler = function( options ){
    var defaults = {
        debug: false
    };
    options = $.extend(true, defaults, options );
    this.init( options );
};

FilemanagerEventHandler.prototype = {
    _events: {},
    _debug: false,

    init: function( config ){
        if( config !== null && typeof config === 'object' ) {
            if (typeof config.debug !== 'undefined') {
                this._debug = config.debug;
            }
        }
    },


    /**
     * Check if event exists
     * @param event
     * @returns {boolean}
     * @param check
     */
    hasEvent: function(event, check) {
        if(this._events.hasOwnProperty(event)) {
            return true
        } else if(check) {
            throw 'No event registered for "'+event+'"';
        }
        return false;
    },

    /**
     * Register event
     * @param event
     * @param callback
     */
    register: function(event, callback) {
        this.debug( "Registering " + event );
        this._create(event); // Create when event doesn't exist
        this._events[event].push(callback);
    },

    /**
     * Remove registered event from collection
     * @param event
     */
    unRegister: function(event) {
        if(this.hasEvent(event, true)) {
            delete this._events[event];
        }
    },

    /**
     * Creates event listener
     * @param event
     */
    _create: function(event) {
        if(!this.hasEvent(event, false)) {
            this._events[event] = [];
        }
    },

    /**
     * Fires registered event
     * @param event
     */
    trigger: function(event) {
        this.debug("Triggering " + event );

        if(this.hasEvent(event, false)) {
            var args = Array.prototype.slice.call(arguments, 1);
            $.each(this._events[event], function(index, method) {
                method.apply(method, args);
            }.bind(this));
        }
    },

    /**
     * Displays debug data
     * @param debug_message
     */
    debug: function( debug_message ) {
        if(this._debug) console.log( debug_message );
    }
};