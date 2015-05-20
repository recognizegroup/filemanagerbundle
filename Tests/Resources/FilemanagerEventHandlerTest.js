'use strict';

describe('FilemanagerEventHandler', function() {
    var eventhandler;

    function initializeEventhandler( config ){
        eventhandler = new FilemanagerEventHandler({});
        eventhandler._events = {};
    }

    it('should be able to register a single event', function () {
        initializeEventhandler();

        eventhandler.register("event", function(){});
        expect( eventhandler._events.hasOwnProperty("event") ).toEqual( true );
    });


    it('should be able to check if it has no events for a specific type', function () {
        initializeEventhandler();

        expect( eventhandler.hasEvent("nonevent") ).toEqual( false );
    });

    it('should be able to check if it has events for a specific type', function () {
        initializeEventhandler();

        eventhandler.register("event", function(){});
        expect( eventhandler.hasEvent("event") ).toEqual( true );
    });

    it('should be able to register multiple listeners for the same event', function () {
        initializeEventhandler();

        eventhandler.register("event", function(){});
        eventhandler.register("event", function(){});
        expect( eventhandler._events.event.length ).toEqual( 2 );
    });

    it('should trigger a listener when an event is triggered', function () {
        initializeEventhandler();
        var triggered = false;
        eventhandler.register("event", function(){
            triggered = true;
        });

        eventhandler.trigger("event");
        expect( triggered ).toEqual( true );
    });

    it('should trigger every listener when an event is triggered', function () {
        initializeEventhandler();
        var triggered = false;
        var triggered2 = false;
        eventhandler.register("event", function(){
            triggered = true;
        });

        eventhandler.register("event", function(){
            triggered2 = true;
        });

        eventhandler.trigger("event");
        expect( triggered && triggered2 ).toEqual( true );
    });

    it('should be able to clear all listeners for the same event', function () {
        initializeEventhandler();

        eventhandler.register("event", function(){});
        eventhandler.register("event", function(){});
        eventhandler.unRegister("event");
        expect( eventhandler._events.event ).toEqual( undefined );
    });



});
