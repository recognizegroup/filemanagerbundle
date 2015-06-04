'use strict';

describe('FilemanagerApi', function() {
    var api;
    var mockeventhandler = {
        trigger: function () {
        },
        register: function () {
        }
    };

    // Disable logs in the output
    var consoleerror;
    beforeAll(function() {
        consoleerror = console.error;
        console.error = function(){};
    });

    afterAll(function() {
        console.error = consoleerror;
    });


    var okReadResponse = function( error ){
        var d = $.Deferred();
        var promise = d.promise();
        d.resolve({ data: { contents: [ {name: "testfile.txt", "directory": "bla", "path": "bla/testfile.txt"} ],
            status: "success" } }, 200, {});

        return promise;
    };

    var okWriteResponse = function( error ){
        var d = $.Deferred();
        var promise = d.promise();

        var changes = [{
           type: "rename", file: {name: "testfile.txt", "directory": "bla", "path": "bla/testfile.txt"},
           updatedfile: {name: "testfile2.txt", "directory": "bla", "path": "bla/testfile2.txt"}
        }];

        d.resolve({ data: { changes: changes,
            status: "success" } }, 200, {});

        return promise;
    };


    var failureResponse = function(){
        var jqXHR = {
            status: 400,
            statusText: "",
            responseText: "{\"data\": {\"message\": \"Error\"}, \"status\": \"fail\"}"
        };

        var d = $.Deferred();
        d.reject( jqXHR, {}, "{\"data\": {\"message\": \"Error\"}, \"status\": \"fail\"}" );
        return d.promise();
    };

    function initializeAjax( config ){
        var defaults = {
            eventHandler: mockeventhandler,
            api: {}
        };
        config = $.extend( defaults, config, true);
        api = new FilemanagerAPI( config );
    }

    // ---------------------- TESTS START HERE

    it('shouldn\'t do an ajax request if the requests are disabled', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:loading", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler,
            api: {
                url: ""
            }
        });

        spyOn($, 'ajax').and.callFake(failureResponse);
        api._sendRequest("none","GET",{});

        expect( triggered ).toEqual( false );
    });


    it('should trigger a loading event when an ajax request has been made', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:loading", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake(failureResponse);
        api._sendRequest("none","GET",{});

        expect( triggered ).toEqual( true );
    });

    it('should trigger a done event when an ajax request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:done", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake(failureResponse);
        api._sendRequest("none","GET",{});

        expect( triggered ).toEqual( true );
    });

    it('should trigger an add_data event when a directory read request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:add_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okReadResponse );
        api.read("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a failure has occurred during a directory read request', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.read("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger a search data event when a search request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:search_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okReadResponse );
        api.search("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a search request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.search("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an update data event when a rename request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:update_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okWriteResponse );
        api.rename("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a rename request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.rename("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an update data event when a move request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:update_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okWriteResponse );
        api.move("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a move request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.move("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an update data event when a create directory request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:update_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okWriteResponse );
        api.createDirectory("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a create directory request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.createDirectory("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an update data event when a delete request has succeeded', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:update_data", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( okWriteResponse );
        api.delete("directory");

        expect( triggered ).toEqual( true );
    });

    it('should trigger an error event when a delete request has failed', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:api:error", function(){
            triggered = true;
        });

        initializeAjax({
            eventHandler: eventhandler
        });

        spyOn($, 'ajax').and.callFake( failureResponse );
        api.delete("directory");

        expect( triggered ).toEqual( true );
    });

});
