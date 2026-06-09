--
-- Connetation Sitepackage – Database Schema
-- TYPO3 LTS 14
--

CREATE TABLE pages (
    tx_conn_base_subtitle        varchar(255) DEFAULT '' NOT NULL,
    tx_conn_base_header_image    int(11) DEFAULT '0' NOT NULL,
    tx_conn_base_hide_breadcrumb tinyint(4) DEFAULT '0' NOT NULL
);
