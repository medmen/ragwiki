<?php

$db = new SQLite3('test.db');



CREATE TABLE IF NOT EXISTS pages
(
    id    INTEGER PRIMARY KEY,
    path  TEXT NOT NULL,
    title TEXT
);

CREATE TABLE IF NOT EXISTS chunks
(
    id        INTEGER PRIMARY KEY,
    page_id   INTEGER NOT NULL,
    heading   TEXT,
    content   TEXT    NOT NULL,
    embedding BLOB    NOT NULL,
    FOREIGN KEY (page_id) REFERENCES pages (id)
);