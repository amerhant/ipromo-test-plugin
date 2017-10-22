<?php

class Itp {

	public function __construct() {
		add_action( 'admin_menu', function () {
			add_options_page( 'ipromo test', 'ipromo test', 8, 'ipromo', array( $this, 'view_admin' ) );
		} );
		add_action( 'admin_enqueue_scripts', function () {
			wp_enqueue_script( 'itp_admin_script', plugins_url( '/ipromo-test-plugin/js/itp_admin_script.js' ), array( 'jquery' ) );

			wp_register_script( 'itp_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js' );
			wp_enqueue_script( 'itp_bootstrap' );
			wp_register_style( 'itp_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css' );
			wp_enqueue_style( 'itp_bootstrap' );
		} );

		add_action( 'admin_post_create_form', array( $this, 'create' ) );
		add_action( 'admin_post_update_form', array( $this, 'update' ) ); 
		add_action( 'wp_ajax_itp_delete', array( $this, 'delete' ) );
		add_shortcode( 'itp_view', array( $this, 'shortcode_view' ) );
		add_filter( 'widget_text', 'do_shortcode' ); //вывод shortcode в сайдбаре
	}

	public function shortcode_view( $atts ) {
		$atts = shortcode_atts( array( 'gender' => false, 'age_max' => false, ), $atts );
		$gender_ar = array( '0' => 'female', '1' => 'male' );
		$artists = $this->getArtists( false, false, $atts['gender'], $atts['age_max'] );
		$result = '
		<div class="container-fluid">';
		foreach ( $artists as $item ) {
			if ( $item->img == null )
				$img = plugins_url( '/ipromo-test-plugin/images/no-photo.png' );
			else
				$img = $item->img;
			$result.= '<hr>
			<table>
				<tr">
					<td>
						<img src="' . $img . '" alt="photo">
					</td>
					<td>
						Full name: ' . $item->full_name . '<br>
						Gender: ' . $gender_ar[$item->gender] . '<br>
						Age:: ' . $item->age . '
					</td> 
				</tr>
			</table>';
		}
		$result.= '</div>';
		echo $result;
	}

	public function view_admin() {
		$limit = 5;
		$page = 1;
		$paged = '';
		if ( isset( $_GET['paged'] ) ) {
			$page = $_GET['paged'];
			$paged = '&paged=' . $page;
		}

		$artists = $this->getArtists( $limit, $page );
		$result = '
		<div class="container-fluid">
			<button id="add_btn" type="button" class="btn btn-success">Add</button>
			<div id="add" class="row" style="display:none;">
				<form name="itp_create" method="POST" enctype="multipart/form-data" action ="' . esc_url( admin_url( 'admin-post.php' ) ) . '">
					<div class="col-sm-4">
						<label for="id">Id:</label>
						<b>new</b><br>
						<div style="width: 230px; height: 213px;">
							<img style="max-width: 100%; max-height: 100%; margin: 0 auto;" id="img_new" src="' . plugins_url( '/ipromo-test-plugin/images/no-photo.png' ) . '" alt="no photo">
						</div>
					</div>
					<div class="col-sm-8"> 
						<label for="full_name">Full name:</label>
						<input class="form-control" name="full_name" type="text" required value="">
						<label for="gender">Gender:</label>
						<select class="form-control" name="gender">
							<option selected value="0">female</option>
							<option value="1">male</option>
						</select>
						<label for="age">Age:</label>
						<input class="form-control" type="number" name="age" required value="">
						' . wp_nonce_field( 'image', 'fileup_nonce' ) . '
						<input id="new" class="load" name="image" type="file"> 
						<input type="hidden" name="action" value="create_form">
						<input type="submit" class="btn btn-primary" name="itp"  value="create">
					</div> 
				</form>
			</div>';

		foreach ( $artists as $item ) {
			if ( $item->img == null )
				$img = plugins_url( '/ipromo-test-plugin/images/no-photo.png' );
			else
				$img = $item->img;
			$result.=' 
			<div class="row artist" name="' . $item->id . '">
				<hr>
				<form name="itp_update" method="POST" enctype="multipart/form-data" action ="' . esc_url( admin_url( 'admin-post.php' ) ) . '">
					<div class="col-sm-4">
						<label for="id">Id:</label>
						<b>' . $item->id . '</b><br>
						<input type="hidden" name="id" value="' . $item->id . '">
						<div style="width: 230px; height: 213px;">
							<img style="max-width: 100%; max-height: 100%; margin: 0 auto;" id="img_' . $item->id . '" src="' . $img . '" alt="no photo">
						</div>
					</div>
					<div class="col-sm-8"> 
						<label for="full_name">Full name:</label>
						<input class="form-control" name="full_name" type="text" value="' . $item->full_name . '">
						<label for="gender">Gender:</label>
						<select class="form-control" name="gender">
							<option ' . (!$item->gender ? 'selected' : '') . ' value="0">female</option>
							<option ' . ($item->gender ? 'selected' : '') . ' value="1">male</option>
						</select>
						<label for="age">Age:</label>
						<input class="form-control" type="number" name="age" value="' . $item->age . '">
						' . wp_nonce_field( 'image', 'fileup_nonce' ) . '
						<input id="' . $item->id . '" class="load" name="image" type="file"> 
						<input type="hidden" name="action" value="update_form">
						<input type="submit" class="btn btn-primary" name="itp"  value="update">
						<input name="' . $item->id . '" type="button" class="btn btn-danger delete_btn" value="delete">
					</div> 
				</form> 
			</div>';
		}

		$result.='<hr>' . $this->getPagination( $limit, $page ) . '</div>';
		echo $result;
	}

	public function create() {
		global $wpdb;

		if ( wp_verify_nonce( $_POST['fileup_nonce'], 'image' ) ) {
			if ( !function_exists( 'wp_handle_upload' ) )
				require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$file = &$_FILES['image'];
			$overrides = array( 'test_form' => false );

			$movefile = wp_handle_upload( $file, $overrides );

			if ( $movefile && empty( $movefile['error'] ) ) {
				$image = wp_get_image_editor( $movefile['file'] );
				$image->resize( 230, 213, false );
				if ( !is_wp_error( $image ) ) {
					$image->save( $movefile['file'] );
				}
			}
		}

		$this->createArtist( $_POST['full_name'], $_POST['gender'], $_POST['age'], $movefile['url'] );
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}

	public function update() {
		global $wpdb;

		if ( wp_verify_nonce( $_POST['fileup_nonce'], 'image' ) ) {
			if ( !function_exists( 'wp_handle_upload' ) )
				require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$file = &$_FILES['image'];
			$overrides = array( 'test_form' => false );

			$movefile = wp_handle_upload( $file, $overrides );

			if ( $movefile && empty( $movefile['error'] ) ) {
				$image = wp_get_image_editor( $movefile['file'] );
				$image->resize( 230, 213, false );
				if ( !is_wp_error( $image ) ) {
					$image->save( $movefile['file'] );
				}
			}
		}
		$this->updateArtist( $_POST['id'], $_POST['full_name'], $_POST['gender'], $_POST['age'], $movefile['url'] );
		wp_redirect( $_SERVER['HTTP_REFERER'] );
	}

	public function delete() {
		$this->deleteArtist( $_POST['id'] );
		wp_die();
	}

	public function getArtists( $limit = false, $page = false, $gender = false, $age_max = false ) {
		global $wpdb;
		$where = '';
		$offset = '';

		if ( ($page != false) & ($limit != false) ) {
			$page = intval( $page );
			$offset = ' OFFSET ' . (($page - 1) * $limit);
		}

		if ( $limit != false ) {
			$limit = ' LIMIT ' . $limit;
		} else
			$limit = '';

		if ( $gender !== false )
			$where = 'gender=' . $gender;
		if ( $age_max !== false ) {
			if ( $where != '' )
				$where.=' AND ';
			$where.='age<=' . $age_max;
		}
		if ( $where != '' )
			$where = 'WHERE ' . $where;

		$sql = 'SELECT * FROM itp_artists ' . $where . $limit . $offset;
		return $wpdb->get_results( $sql, OBJECT );
	}

	static function install() {
		global $wpdb;
		$sql = "
			CREATE TABLE IF NOT EXISTS `itp_artists` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`full_name` varchar(100) NOT NULL,
			`gender` tinyint(1) NOT NULL,
			`age` int(3) NOT NULL,
			`img` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		$wpdb->query( $sql );
	}

	private function getPagination( $limit, $page ) {
		global $wpdb;

		$big = 999999999; // уникальное число для замены

		$args = array(
			'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
			'format' => '',
			'current' => $page,
			'total' => ceil( $wpdb->get_results( 'SELECT COUNT(1) AS `count` FROM itp_artists', OBJECT )[0]->count / $limit ),
		);

		$result = paginate_links( $args );

		// удаляем добавку к пагинации для первой страницы
		$result = str_replace( '/page/1/', '', $result );

		return $result;
	}

	public static function createArtist( $full_name, $gender, $age, $image_url = false ) {
		global $wpdb;
		$errors = FALSE;

		$full_name = sanitize_text_field( $full_name );
		$gender = wp_validate_boolean( $gender );
		$age = absint( $age );

		if ( $full_name == '' ) {
			$errors[] = 'ФИО не может быть пустым.';
		}
		if ( $image_url == false ) {
			$image_url = null;
		} else
			$image_url = esc_url( $image_url );

		if ( !$errors ) {
			return $wpdb->insert( 'itp_artists', array( 'full_name' => $full_name, 'gender' => $gender, 'age' => $age, 'img' => $image_url ), array( '%s', '%d', '%d', '%s' ) );
		} else
			return $errors;
	}

	public static function updateArtist( $id, $full_name, $gender, $age, $image_url = false ) {
		global $wpdb;
		$errors = FALSE;

		$id = absint( $id );
		$full_name = sanitize_text_field( $full_name );
		$gender = wp_validate_boolean( $gender );
		$age = absint( $age );

		$item = $wpdb->get_row( "SELECT img FROM itp_artists WHERE id = " . $id );

		if ( $item == null ) {
			$errors[] = 'Такого артиста не существует.';
		}
		if ( $full_name == '' ) {
			$errors[] = 'ФИО не может быть пустым.';
		}
		if ( $image_url == false ) {
			$image_url = $item->img;
		} else
			$image_url = esc_url( $image_url );

		if ( !$errors ) {
			return $wpdb->update( 'itp_artists', array( 'full_name' => $full_name, 'gender' => $gender, 'age' => $age, 'img' => $image_url ), array( 'id' => $id ), array( '%s', '%d', '%d', '%s' ), array( '%d' )
			);
		} else
			return $errors;
	}

	public static function deleteArtist( $id ) {
		global $wpdb;
		return $wpdb->delete( 'itp_artists', array( 'id' => $id ), array( '%d' ) );
	}

}
