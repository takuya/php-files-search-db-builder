-- noinspection SqlNoDataSourceInspectionForFile
CREATE TABLE IF NOT EXISTS locates (
    id  INTEGER PRIMARY KEY AUTOINCREMENT, -- 行を一意に識別するための主キー
    filename TEXT NOT NULL,
    mtime INTEGER, -- Unixエポック秒を格納する INTEGER 型
    ctime INTEGER, -- Unixエポック秒を格納する INTEGER 型
    size INTEGER
);

-- 2. size, mtime, ctime カラムのソートを高速化するためのインデックス (元のテーブルに作成)
CREATE INDEX IF NOT EXISTS idx_size ON locates (size);
CREATE INDEX IF NOT EXISTS idx_mtime ON locates (mtime);
CREATE INDEX IF NOT EXISTS idx_ctime ON locates (ctime);
CREATE INDEX IF NOT EXISTS idx_filename_prefix ON locates (filename);

-- 3. filename カラムの全文検索を行うための FTS5 仮想テーブルの作成
CREATE VIRTUAL TABLE IF NOT EXISTS locates_fts USING fts5(
    filename,
    content=locates,
    tokenize=trigram
--    tokenize='icu ja_JP'
--     tokenize='unicode61 remove_diacritics 1' -- 必要に応じて 'icu ja_JP' などに変更

);

-- 4. 元の locates テーブルから locates_fts テーブルへの初期データ投入
INSERT INTO locates_fts (rowid, filename)
SELECT id, filename  FROM locates;

-- 5. locates テーブルへの INSERT 操作を locates_fts テーブルに反映するトリガー
CREATE TRIGGER IF NOT EXISTS locates_ai AFTER INSERT ON locates BEGIN
    INSERT INTO locates_fts (rowid, filename) VALUES (new.id, new.filename );
END;

-- 6. locates テーブルへの DELETE 操作を locates_fts テーブルに反映するトリガー (変更)
CREATE TRIGGER IF NOT EXISTS locates_ad AFTER DELETE ON locates BEGIN
DELETE FROM locates_fts WHERE rowid = old.id;
END;

-- 7. locates テーブルへの UPDATE 操作を locates_fts テーブルに反映するトリガー (変更)
CREATE TRIGGER IF NOT EXISTS locates_au AFTER UPDATE ON locates BEGIN
UPDATE locates_fts SET filename = new.filename  WHERE rowid = old.id;
END;