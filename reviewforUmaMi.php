<?php
/*
Plugin Name: ReviewForUmami
Plugin URI: http://www.example.com/plugin
Description: UmaMi用レビュープラグイン
Author: Taka
Version: 0.2.7
Author URI: http://www.example.com
*/
/*  Copyright 2018 Taka (email : otaotaota55@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class UmamiReviewOptionPage {
  var $table_name;
    function __construct() {
      add_action('admin_menu', array($this, 'add_pages'));
      global $wpdb;
      $this->table_name = $wpdb->prefix . 'reviewtableforUmaMi';
    }
    function add_pages() {
      add_menu_page('レビュー','レビュー',  'level_8', __FILE__, array($this,'show_Umami_option_page'), '', 26);
    }
    function show_Umami_option_page() {
        if ( isset($_POST['showtext_options'])) {
            check_admin_referer('shoptions');
            $opt = sanitize_key($_POST['showtext_options']);
            update_option('showtext_options', $opt);
            ?><div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div><?php
        }
        if(isset($_POST['language'])){
          check_admin_referer('shoptions');
          $lang = sanitize_key($_POST['language'],"sp");
          if($lang=="sp"||$lang=="jp"){
          update_option('language_options', $lang);
          }
        }
          if(isset($_POST['del_id'])){
            check_admin_referer('del_id_options');
            $del_id=array_map('absint',$_POST['del_id']);
            global $wpdb;
            $max=count($del_id);
            for($count=0;$count<=$max;$count++){
            $wpdb->delete($this->table_name, array( 'meta_id' => $del_id[$count]) );
            ?><div class="updated fade"><p><strong><?php _e('選択項目が削除されました'); ?></strong></p></div><?php
            }
          }

        ?>
    <div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div><h2>レビュープラグイン</h2>
        <form action="" method="post">
            <?php
            wp_nonce_field('shoptions');
            $opt = get_option('showtext_options');
            $show_text = isset($opt['text']) ? $opt['text']: null;
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="inputtext">リダイレクトページ指定</label></th>
                    <td><input name="showtext_options[text]" type="text" id="inputtext" value="<?php  echo $show_text ?>" class="regular-text" /></td>
                </tr>
            </table>
            日本語<input type="radio" id="language" name="language" value="jp"/>

            スペイン語<input type="radio" id="language" name="language" value="sp"/>

            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
<br>
</form>
        <div>
        [showavgstar]<br>
        :そのページの平均星評価を表示<br>
        [showavgstar page_id]<br>
        page_idの平均星評価を表示<br>
        [showlatest5reviews]<br>
        :最新５件のレビュー表示<br>
        [add_to_database]<br>
        リダイレクトページに表示して投稿完了処理<br>
        [form_redirect]
        :フォーム表示、submitの後は投稿完了表示<br>
      </div>
      <form action="" method="post">
<?php
      wp_nonce_field('del_id_options');
      global $wpdb;
      $results = $wpdb->get_results("SELECT * FROM $this->table_name");
      $max=count($results);
?>
        <input type="submit" name="消去" class="button-primary" value="選択した行を削除" />
        <table border="1" bordercolor="#333333">
          <tr>
            <th width="20">番号</th>
            <th width="50">記事番号</th>
            <th width="200">日時</th>
            <th width="200">名前</th>
            <th width="200">メールアドレス</th>
            <th width="20">星評価</th>
            <th width="200">タイトル</th>
            <th width="200">コメント</th>
            <th>選択</th>
          </tr>
          <?php
          for($count=0; $count<$max; $count++){
            ?>
          <tr>
            <th><?php echo $results[$count]->meta_id; ?></th>
            <th><?php echo $results[$count]->post_id; ?></th>
            <th><?php echo $results[$count]->review_date; ?></th>
            <th><?php echo $results[$count]->username; ?></th>
            <th><?php echo $results[$count]->mailaddress; ?></th>
            <th><?php echo $results[$count]->star_score; ?></th>
            <th><?php echo $results[$count]->title; ?></th>
            <th><?php echo $results[$count]->comment; ?></th>
            <th><input type="checkbox" name="del_id[]" value="<?php echo $results[$count]->meta_id; ?> "></th>
          </tr>
          <?php
        }
        ?>
        </table><br>
        <input type="submit" name="消去" class="button-primary" value="選択した行を削除" />
      </form>
    </div>

    <?php
}
    }
//フォームの表示、評価の平均値を星で表示
class ReviewForUmami{
    var $table_name;
    var $plugin_file_dir;
    public function __construct(){
      add_shortcode('reviewform', array($this,'Umami_review_form'));
      add_shortcode('showreviewstar', array($this,'show_Umami_review_star'));
      add_shortcode('showuserreviews', array($this,'show_Umami_user_reviews'));
      add_shortcode('showavgstar', array($this,'show_Umami_avg_star'));
      add_shortcode('showlatest5reviews', array($this,'show_Umami_latest_5reviews'));
      add_shortcode('form_redirect', array($this,'Umami_form_redirect'));
      add_shortcode('add_to_database', array($this,'add_to_Umami_database'));
				//add_action('trim_title',array($this,'show_Umami_avg_star'));
      global $wpdb;
      $this->table_name = $wpdb->prefix . 'reviewtableforUmaMi';
      $this->plugin_file_dir=plugins_url( '', __FILE__ );
    }
    function show_Umami_review_star($star_point_){
      $star_path = $this->plugin_file_dir.'/img/スター.png?';
      $star_back_path = $this->plugin_file_dir.'/img/バックスター.png?';
      $avg=$star_point_*24;
      $str=<<<EOD
            <span class="star-rating-output">
                <span class="star-rating-front"  style="width:{$avg}px; object-fit: cover; position:absolute; overflow:hidden;">
                  <img src="{$star_path}" style="height:21px; object-fit: cover; object-position: 0 100%">
                </span>
                <span class="star-rating-back">
                  <img src="{$star_back_path}" style="height:21px;">
                </span>
            </span>
EOD;
      return $str;
    }

      function show_Umami_avg_star($post_id_){
          global $wpdb;
          $post_id=get_the_ID();
          //if($post_id!=0){
          //  $post_id=$post_id_;
          //}
          $avg=$wpdb->get_var( $wpdb->prepare(
              "SELECT AVG(star_score) FROM $this->table_name WHERE post_id = %s",
              $post_id ));
        $str=self::show_Umami_review_star($avg);
        if($avg>0&&$avg<=5){
        $str.=round($avg,2);
      }
        return $str;
      }

    function show_Umami_user_reviews($meta_id_){
      global $wpdb;
      $meta_id=$meta_id_;
      $post_id=get_the_ID();
      $lang=get_option('language_options');
      if($lang=="jp"){
        $str_year_suffix="年前";
        $str_month_suffix="ヶ月前";
        $str_day_suffix="日前";
        $str_today="今日";
      }else if($lang=="sp"){
        $str_year_prefix="hace ";
        $str_year_suffix=" ano";
        $str_month_prefix="hace ";
        $str_month_suffix=" mes";
        $str_day_prefix="hace ";
        $str_day_suffix=" dia";
        $str_today="hoy";
      }else{
        $str_year_suffix="year ago";
        $str_month_suffix="month ago";
        $str_day_suffix="day ago";
        $str_today="today";
      }
      $star=$wpdb->get_var( $wpdb->prepare(
        "SELECT star_score FROM $this->table_name WHERE meta_id = %s and post_id = %s",
        $meta_id,
        $post_id
      ) );
      $comment=$wpdb->get_var( $wpdb->prepare(
        "SELECT comment FROM $this->table_name WHERE meta_id = %s and post_id = %s",
        $meta_id,
        $post_id
      ) );
      $username=$wpdb->get_var( $wpdb->prepare(
        "SELECT username FROM $this->table_name WHERE meta_id = %s and post_id = %s",
        $meta_id,
        $post_id
      ) );
      $review_date=$wpdb->get_var( $wpdb->prepare(
        "SELECT review_date FROM $this->table_name WHERE meta_id = %s and post_id = %s",
        $meta_id,
        $post_id
      ) );
      $title=$wpdb->get_var( $wpdb->prepare(
        "SELECT title FROM $this->table_name WHERE meta_id = %s and post_id = %s",
        $meta_id,
        $post_id
      ) );
      $title=esc_html($title);
      $username=esc_html($username);
      $comment=esc_html($comment);

      $review_date=date('Y-m-d',strtotime($review_date));
      if(strtotime($review_date)==0){
        $time_diff=0;
      }else{
      $time_diff=(strtotime(date("Y-m-d"))-strtotime($review_date))/86400;
    }
      $month_past=0;
      $year_past=0;
      while($time_diff>=30){
        $month_past+=1;
        $time_diff-=30;
      }
        while($month_past>=12){
          $year_past+=1;
          $month_past-=12;
        }
      if($year_past>0){
        $time_diff=$str_year_prefix.$year_past.$str_year_suffix;
      }else if($month_past>0){
        $time_diff=$str_month_prefix.$month_past.$str_month_suffix;
      }else if($time_diff<30&&$time_diff>0){
        $time_diff=$str_day_prefix.$time_diff.$str_day_suffix;
      }else if($time_diff==0){
          $time_diff=$str_today;
        }
      $star_str=self::show_Umami_review_star($star);
      $str=<<<EOD
         <div class="user_reviews">
                  <div class="nakami">
          {$star_str}
            <div class="time_name">
            {$time_diff}
            {$username}
            </div>
          <div class="title">{$title}</div>

          <div class="comment">{$comment}</div>
        </div></div>
EOD;
      return $str;
    }
//レビューフォーム表示
    function Umami_review_form(){
      $post_id=get_the_ID();
      $lang=get_option('language_options');
      if($lang=="jp"){
        $str_review_form="レビューフォーム";
        $str_mailaddress="メールアドレス";
        $str_title="タイトル";
        $str_username="名前";
        $str_comment="コメント";
        $str_submit="送信する";
      }else if($lang=="sp"){
        $str_review_form="formrario de revision";
        $str_mailaddress="correo_electronico";
        $str_title="asunto";
        $str_username="nombre";
        $str_comment="comentario";
        $str_submit="enviar";
      }else{
        $str_review_form="review form";
        $str_mailaddress="e-mail";
        $str_title="title";
        $str_username="name";
        $str_comment="comment";
        $str_submit="submit";
      }
      $star_path = $this->plugin_file_dir.'/img/スターA.png?';
      $star_back_path = $this->plugin_file_dir.'/img/バックスターA.png?';
      $nonce = wp_create_nonce( 'my-nonce' );
      echo "<a href='reviewforUmaMi.php?_wpnonce={$nonce}'></a>";
    //  $css_url=$this->plugin_file_dir."/css/style.css";
    $urlpath = $this->plugin_file_dir."/css/style.css";
    wp_register_style('reviewcss', $urlpath);
    wp_enqueue_style('reviewcss');
    $str=  <<< EOD
    <script>
    $(':radio').change(
  function(){
    $('.choice').text( this.value + ' stars' );
  }
)
    </script>
            <div class="review_form">
            <div class="nakami">
            <br>
            <h2>{$str_review_form}</h2>
          <form method="post" action="" id="f1">
          <input type="hidden" id="post_id" name="post_id" value="{$post_id}" />
          <input type="hidden" id="_wpnonce" name="_wpnonce" value="{$nonce}" />
             <br><input type="text" placeholder="{$str_username}"  id="name" name="username" required/>
             <span class="help-text"></span><br>
             <input type="text" placeholder="{$str_mailaddress}"  id="mailaddress" name="mailaddress" />
             <span class="help-text"></span><br>
             <input type="text" placeholder="{$str_title}"  name="title" required /><br>
             <span class="help-text"></span>
             <div class="star-rating">
               <input type="radio" id="star1" name="rating" value="1"  required/><i></i>
               <input type="radio" id="star2" name="rating" value="2"/><i></i>
               <input type="radio" id="star3" name="rating" value="3"/><i></i>
               <input type="radio" id="star4" name="rating" value="4"/><i></i>
               <input type="radio" id="star5" name="rating" value="5"/><i></i>

             </div><br><br>
             <textarea placeholder="{$str_comment}" name="comment" rows="10" cols="40"　required></textarea><br>
             <span class="help-text"></span>
             <div class="submit_button">
             <input type="submit" value="{$str_submit}">
             </div>
             <br>
             </form>
             </div>
             </div>
EOD;
        return $str;
    }

    function show_Umami_latest_5reviews(){
      global $wpdb;
      $lang=get_option('language_options');
      if($lang=="jp"){
        $str_review_count_prefix="レビュー";
        $str_review_count_suffix="件";
      }else if($lang=="sp"){
        $str_review_count_prefix="";
        $str_review_count_suffix="revisons";
      }else{
        $str_review_count_prefix="";
        $str_review_count_suffix="reviews";
      }
      $post_id=get_the_ID();
      $latest_meta_id=$wpdb->get_col( $wpdb->prepare(
        "SELECT meta_id FROM $this->table_name WHERE post_id = %s ORDER BY meta_id DESC LIMIT 5",
        $post_id
      ) );
      $count=count($latest_meta_id);
      $str="<br>".$str_review_count_prefix.$count.$str_review_count_suffix."<br>";
      for ($count=0; $count<count($latest_meta_id); $count++){
        $str.=self::show_Umami_user_reviews($latest_meta_id[$count]);
      }
      return $str;
    }
    function Umami_form_redirect(){
      if(!empty($_POST["post_id"])) {
        $str=self::add_to_Umami_database();
      }else{
        $str=self::Umami_review_form();
      }
      return $str;
    }
    //データベースにフォームからのデータ追加
        function add_to_Umami_database(){
            $lang=get_option('language_options');
            if($lang=="jp"){
                $str_success="投稿完了しました";
                $str_failed="投稿できませんでした";
            }else if($lang=="sp"){
                $str_success="enviado!";
                $str_failed="error, algo salio mal";
            }else{
                $str_success="submitted!";
                $str_failed="Sorry, something went wrong";
            }
            global $wpdb;
            $review_date=date("Y-m-d H:i:s");
            $nonce = $_POST['_wpnonce'];
            if ( ! wp_verify_nonce( $nonce, 'my-nonce' ) ) {
              echo "エラー";
            } else {
        if ( isset($_POST['comment'])&&isset($_POST['username'])&&isset($_POST['post_id'])&&isset($_POST['rating'])&&isset($_POST['title'])) {
            $comment = sanitize_text_field(htmlspecialchars($_POST['comment']));
            $mailaddress=sanitize_email(htmlspecialchars($_POST['mailaddress']));
            $username=sanitize_user(htmlspecialchars($_POST['username']),false);
            //$user_id=sanitize_key(htmlspecialchars($_POST['user_id']));
            $starscore=sanitize_key(htmlspecialchars($_POST['rating']));
            $post_id=sanitize_key(htmlspecialchars($_POST['post_id']));
            $title=sanitize_text_field(htmlspecialchars($_POST['title']));
            $wpdb->query( $wpdb->prepare(
    	         "
    		         INSERT INTO $this->table_name
    		           ( post_id, comment, star_score, username, title, review_date )
    		             VALUES ( %d, %s, %f,%s,%s,%s )
    	         ",
               array(
                 'post_id'=> $post_id,
                 'comment' => $comment,
                 'star_score' => $starscore,
                 'username'=>$username,
                 'title'=>$title,
                 'review_date'=>$review_date,
                )
                ) );
              }else{
                return $str_failed;
              }

        $str=<<< EOD
        <div class="submited">
        <div class="nakami">
        <br>
        {$str_success}<br>
        </div><br></div>
EOD;

        return $str;
      }
      }
}


    class TableForUmamiReview{
      var $table_name;
      public function __construct()
      {
          global $wpdb;
          $this->table_name = $wpdb->prefix . 'reviewtableforUmaMi';
          register_activation_hook (__FILE__, array($this, 'cmt_activate'));
      }
      function cmt_activate() {
          global $wpdb;
          $cmt_db_version = '1.1';
          $installed_ver = get_option( 'cmt_meta_version' );
          if( $installed_ver != $cmt_db_version ) {
            $sql = "CREATE TABLE " . $this->table_name . " (
              meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              post_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
              user_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
              review_date datetime NOT NULL,
              username text NOT NULL,
              mailaddress text NOT NULL,
              title text NOT NULL,
              comment text NOT NULL,
              star_score bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
              UNIQUE KEY meta_id (meta_id)
            )
            CHARACTER SET 'utf8';";
          require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          dbDelta($sql);
          update_option('cmt_meta_version', $cmt_db_version);
    }
}

  }
    $exmeta = new TableForUmamiReview;
    $showtext = new UmamiReviewOptionPage;
    $RFU = new ReviewForUmami;
 ?>
