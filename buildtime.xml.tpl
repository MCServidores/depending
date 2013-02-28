<?xml version="1.0" encoding="UTF-8"?>
<config>
  <propel>
    <datasources default="depending">
      <datasource id="depending">
        <adapter>mysql</adapter>
        <connection>
          <dsn>mysql:host=localhost;dbname=depending</dsn>
          <user>depending_user</user>
          <password>depending_password</password>
        </connection>
      </datasource>
    </datasources>
  </propel>
</config>