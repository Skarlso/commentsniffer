<?php

class DBConnector
{
    protected $db;
    public function __construct()
    {
        $this->db = new SQLite3('facebook.db');
    }

    public function prepareDB()
    {
        unlink('facebook.db');
        $this->db = new SQLite3('facebook.db');
        $this->db->exec('CREATE TABLE comments (groupid STRING, postid STRING, commentid STRING, comment STRING)');
    }

    public function exists()
    {
        return file_exists('facebook.db');
    }

    public function insertComment($groupid, $postid, $commentid, $comment)
    {
        $stmt = $this->db->prepare('INSERT INTO comments VALUES (:groupid, :postid, :commentid, :comment)');
        $stmt->bindValue(':groupid', $groupid, SQLITE3_TEXT);
        $stmt->bindValue(':postid', $postid, SQLITE3_TEXT);
        $stmt->bindValue(':commentid', $commentid, SQLITE3_TEXT);
        $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function getComment($groupid, $postid, $commentid)
    {
        $stmt = $this->db->prepare("SELECT comment FROM comments WHERE groupid=:groupid AND postid=:postid AND commentid=:commentid");
        $stmt->bindValue(':groupid', $groupid, SQLITE3_TEXT);
        $stmt->bindValue(':postid', $postid, SQLITE3_TEXT);
        $stmt->bindValue(':commentid', $commentid, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray();
    }
}
