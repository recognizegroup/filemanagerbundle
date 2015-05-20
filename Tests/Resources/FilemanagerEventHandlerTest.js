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

});
