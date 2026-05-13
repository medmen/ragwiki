<?php

$db = new SQLite3('rag.sqlite');

$db->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY, path TEXT, title TEXT)');
$db->exec('CREATE TABLE chunks (id INTEGER PRIMARY KEY, page_id INTEGER NOT NULL, heading TEXT, content TEXT, embedding BLOB, FOREIGN KEY (page_id) REFERENCES pages(id))');


