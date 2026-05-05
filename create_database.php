<?php

$db = new SQLite3('rag.sqlite');

$db->exec('CREATE TABLE chunks (page_id INTEGER PRIMARY KEY, heading TEXT, content TEXT, embedding BLOB)');
$db->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY, path TEXT, title TEXT)');


