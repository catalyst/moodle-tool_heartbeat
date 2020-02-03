# To build a zip file for upload to the moodle plugin directory
zip: tool_heartbeat.zip

tool_heartbeat.zip:
	@mkdir -p build
	@cd .. && zip -r --exclude=heartbeat/build/ --exclude=heartbeat/Makefile heartbeat/build/tool_heartbeat.zip heartbeat/*

clean:
	@$(RRM) build

