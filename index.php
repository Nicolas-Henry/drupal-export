<?php

/**
  * Drupal Export V 0.3 (Mai 02, 2017)
  * Drupal 7 export mysql database to a simple mysql database schema.
  * This script can be used if you want to retrieve data from the drupal database for another use. For example to remake the site in PHP langage (homemade) or to use it on a PHP framework.
  * NB: php or a another langage ;-)
  *
  * Export users
  *     - Reindex user id : Very handy if you have undergone the drupal harsh hack.
  *     - Only exports users who have nodes (articles).
  *      
  * Export the nodes
  *     -  Type 'articles' (a custom node : title / field_url / body / field_categories / path)
  *             - You can change the code to copy another node type : lines 103 to 165
  *
  * WARNING 1 : this script delete all tables defined in settings.php (by default : users & articles tables) !!!
  *
  * WARNING 2 : this script doesn't export all Drupal7 database datas !!!
  *
  * WARNING 3 : no security control (sql injections, ...) !!!
**/

// includes
include('./inc/settings.php');
include('./inc/db-functions.php');
include('./inc/header.html');

// title
echo '<div class="well text-center">';
echo "<h1>DRUPAL 7 Export Database</h1>";

// databases connection
echo "<h2>Drupal7 db connexion</h2>";
$mysqli_drupal = connect_db($settings_db_drupal7);
echo $mysqli_drupal->host_info . "\n<br /><br />";

echo "<h2>Destination db connexion</h2>";
$mysqli_destination = connect_db($settings_db_destination);
echo $mysqli_destination->host_info . "\n<br /><br />";

echo '</div>';
echo '<hr />';

// test if tables exist in db-destination ; if exist then delete Tables
echo '<div class="well text-center">';
foreach ($tables as $key=>$value) {
    echo "<h2>Table ".$value."</h2>";
    if (tableExist($value, $mysqli_destination)) {
        echo "table ".$value." exist => delete<br /><br />";
        if(deleteTable($value, $mysqli_destination)) {
            echo "delete ok<br />";
        } else {
            echo "delete error<br />";
            echo '</div>';
            die();
        }
    } 
    echo "<br />";
    
    // table doesn't exist = deleted or not existed
    // create tables
    if(createTable($value, $tables_create_sql[$key], $mysqli_destination)) {
        echo "create ok<br />";
    } else {
        echo "create bad<br />";
        echo '</div>';
        die();
    }   
}
echo '</div>';
echo '<hr />';

// import categories
$read_categories_sql = "SELECT tid, name, description FROM taxonomy_term_data WHERE vid = ".$category_vid;
$categories_result = execQueryOrDie($read_categories_sql, $mysqli_drupal);
while($row_categories = $categories_result->fetch_assoc()) {
    // parent category
    $read_parent_category_sql = "SELECT parent FROM taxonomy_term_hierarchy WHERE tid = ".$row_categories['tid'];
    $parent_category_result = execQueryOrDie($read_parent_category_sql, $mysqli_drupal);
    $row_parent_category = $parent_category_result->fetch_assoc();
    
    // mysql prepare
    $row_categories['name'] = $mysqli_drupal->real_escape_string($row_categories['name']);
    $row_categories['description'] = $mysqli_drupal->real_escape_string($row_categories['description']);
    
    // alias url   
    $read_url_alias_sql ="SELECT alias FROM url_alias WHERE source = 'taxonomy/term/".$row_categories['tid']."'";
    $url_alias_result = execQueryOrDie($read_url_alias_sql, $mysqli_drupal);
    $row_url_alias = $url_alias_result->fetch_assoc();
       
    // insert categories into new database 
    $insert_categories_sql = "INSERT INTO categories(id, category_id, name, slug, description) VALUES
    (
        ".$row_categories['tid'].",
        ".$row_parent_category['parent'].",
        '".$row_categories['name']."',
        '".$row_url_alias['alias']."',
        '".$row_categories['description']."
    ')";           
    $result_insert_categories = execQueryOrDie($insert_categories_sql, $mysqli_destination);    
}

// read users Drupal 7 table
echo '<div class="well text-center">';
echo "<h2>Read users Drupal 7 table (export users and their articles)</h2>";
$read_users_sql = "SELECT * FROM users";
$user_result = execQueryOrDie($read_users_sql, $mysqli_drupal);

// for each user read datas (articles)
$new_user_id = 1;
while($user_row = $user_result->fetch_assoc()) {
	echo '<h3>'.$user_row['uid'] . ' -> '.$user_row['name']. ' ==> '.$new_user_id.'</h3>';

    // select all articles of a user
    $read_articles_sql = "SELECT * FROM node WHERE uid = ".$user_row['uid']." AND type LIKE 'article'";
    $articles_of_user_result = execQueryOrDie($read_articles_sql, $mysqli_drupal);
    $nb_articles_user = mysqli_num_rows($articles_of_user_result);
    echo "Number of articles : ".$nb_articles_user."<br />";

    // insert user articles   
    if($nb_articles_user >0) {        
        // read all articles of the user
        echo '<table class="table table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>Article datas</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while($row_article_node = $articles_of_user_result->fetch_assoc()) {
            // node "base" fields
             
            echo '<tr>';
            echo '<td>'.$row_article_node['nid'].'</td>';
            echo '<td>'.$row_article_node['title'] .' || status : '.$row_article_node['status'].' || created : '.$row_article_node['created'].' | changed : '.$row_article_node['changed'].'</td>';
            echo '</tr>';
            
            /* date */
            $new_date = date('Y-m-d H:i:s', $row_article_node['created']+3600);				
            $new_date_changed = date('Y-m-d H:i:s', $row_article_node['changed']+3600);
            
            /* title */
            $row_article_node['title'] = prepareText($row_article_node['title'], $mysqli_drupal);
            
            /** specific fields (fields added to node) **/

            /* url */
            $read_article_url_sql = "SELECT field_url_url FROM field_data_field_url WHERE entity_id = ".$row_article_node['nid'];
            $article_url_result = execQueryOrDie($read_article_url_sql, $mysqli_drupal);
            $row_article_url = $article_url_result->fetch_assoc();

            /* content & summary*/
            $read_article_body_sql = "SELECT body_value, body_summary FROM field_data_body WHERE entity_id = ".$row_article_node['nid'];
            $article_body_result = execQueryOrDie($read_article_body_sql, $mysqli_drupal);
            $row_article_body = $article_body_result->fetch_assoc();
            
            $row_article_body['body_summary'] = prepareText($row_article_body['body_summary'], $mysqli_drupal);
            $row_article_body['body_value'] = prepareText($row_article_body['body_value'], $mysqli_drupal);
                        
            /* category */
            $read_article_category_id_sql = "SELECT field_categories_tid FROM field_data_field_categories WHERE entity_id = ".$row_article_node['nid'];
            $article_category_id_result = execQueryOrDie($read_article_category_id_sql, $mysqli_drupal);
            $row_article_category_id = $article_category_id_result->fetch_assoc();
            
            /* counter */
            $read_article_views_sql = "SELECT totalcount FROM node_counter WHERE nid = ".$row_article_node['nid'];
            $article_views_result = execQueryOrDie($read_article_views_sql, $mysqli_drupal);
            $row_arcticle_views = $article_views_result->fetch_assoc();

            /** Insert datas article into the new database **/
            $insert_articles_sql = "INSERT INTO articles(id, user_id, name, description, category_id, url, content, views, online, created, modified) VALUES
            (
                ".$row_article_node['nid'].",
                $new_user_id,
                '".$row_article_node['title']."',
                '".$row_article_body['body_summary']."',
                '".$row_article_category_id['field_categories_tid']."',
                '".$row_article_url['field_url_url']."',
                '".$row_article_body['body_value']."',
                ".$row_arcticle_views['totalcount'].",
                ".$row_article_node['status'].",
                '".$new_date."',
                '".$new_date_changed."
            ')";          
            $result_insert_articles = execQueryOrDie($insert_articles_sql, $mysqli_destination);
        } // end while
        echo '</tbody>';
        echo '</table>';
        
        // insert user into new database (db-destination)
        print_r($user_row);
        echo "<br /><hr />";
        
        $new_username   = $mysqli_destination->real_escape_string($user_row['name']);
        $new_email      = $mysqli_destination->real_escape_string($user_row['mail']);
        $new_is_active  = $user_row['status'];
        $new_lastlogin  = date("Y-m-d H:i:s", $user_row['login']);
        $new_lastaccess = date("Y-m-d H:i:s", $user_row['access']);
        $new_created    = date("Y-m-d H:i:s", $user_row['created']);
        
        if($user_row['login'] == 0) {
            $values_format = "(%d, '%s', '%s', %d, '%s', NULL, NULL)";    
        }
        else {
            $values_format = "(%d, '%s', '%s', %d, '%s', '%s', '%s')";
        }
        
        $insert_user_sql = sprintf("INSERT INTO users(id, username, email, is_active, created, lastlogin, lastaccess) VALUES ".$values_format ,
            $new_user_id,
            $new_username,
            $new_email,
            $new_is_active,
            $new_created,
            $new_lastlogin,
            $new_lastaccess
        );
        print_r($insert_user_sql);
               
        $result_insert_user = execQueryOrDie($insert_user_sql, $mysqli_destination);
        
        $new_user_id ++; // new user ID
    } // end if nbArticlesUser > 0
    else {
        echo "This user have no articles => user not created !<br /><br />";
    }
} //end while
echo '</div>';

include('./footer.html');
?>