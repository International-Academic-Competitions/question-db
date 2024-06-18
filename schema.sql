CREATE TABLE packets (
  packet_id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  filename TEXT NOT NULL,
  filepath TEXT NOT NULL
) STRICT;

CREATE TABLE questions (
  question_id INTEGER PRIMARY KEY,
  packet_id INTEGER REFERENCES packets ON DELETE CASCADE,
  num INTEGER,
  question TEXT NOT NULL,
  answer TEXT NOT NULL,
  type TEXT
) STRICT;
