<?php

test('example', function () {

    expect(true)->toBeTrue();
});

test ('example to call embed via curl', function () {
    curl -s -X POST "http://localhost:8041/embed" \
  -H "Content-Type: application/json" \
  -d '{"texts":["Hallo Welt, das ist ein Test."]}'
});
