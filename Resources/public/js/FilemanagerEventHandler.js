"use strict";

var FilemanagerEventHandler = function(){};

FilemanagerEventHandler.prototype = {
    _events: {},

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
        console.log("Triggering " + event );

        if(this.hasEvent(event, false)) {
            var args = Array.prototype.slice.call(arguments, 1);
            $.each(this._events[event], function(index, method) {
                method.apply(method, args);
            }.bind(this));
        }
    }
};