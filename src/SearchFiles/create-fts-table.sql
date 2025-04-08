PRAGMA auto_vacuum = 1;
-- noinspection SqlNoDataSourceInspectionForFile
CREATE TABLE IF NOT EXISTS locates (
    id  INTEGER PRIMARY KEY AUTOINCREMENT, -- 行を一意に識別するための主キー
    filename TEXT NOT NULL UNIQUE,
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
    id UNINDEXED,
    filename,
    mtime UNINDEXED,
    ctime UNINDEXED,
    size UNINDEXED,
    content=locates,
    content_rowid='id',
    tokenize = "trigram"
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

-- bigramは無理そう
-- 日本語ではbigramをつくるのはめんどくさい
-- CREATE VIRTUAL TABLE trigram_fts_vocab USING fts5vocab( locates_fts , row );
-- 数GBならメモリに載るので、LIKEでも正直そこまで速度低下は気にならない。
-- ど、うしてもやりたい場合は、むちゃすることもできるが・・・
-- https://www.space-i.com/post-blog/sqlite-fts-trigram-tokenizer%E3%81%A7unigram%EF%BC%86bigram%E6%A4%9C%E7%B4%A2%E3%81%BE%E3%81%A7%E3%82%B5%E3%83%9D%E3%83%BC%E3%83%88-%E6%97%A5%E6%9C%AC%E8%AA%9E%E5%85%A8%E6%96%87%E6%A4%9C%E7%B4%A2/

