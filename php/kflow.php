<?php
namespace pickles2\px2kflow;

/**
 * px2-kflow
 */
class kflow {

	/**
	 * kflow変換処理の実行
	 * @param object $px Picklesオブジェクト
	 * @param object $plugin_options プラグイン設定
	 */
	public static function processor( $px, $plugin_options ){
		$plugin_options = (object) $plugin_options;
		$tmp_hash = substr(md5(microtime()), 0, 16);
		$realpath_files_base = $px->realpath_files_cache();
		$utils = new Utils();

		// --------------------------------------
		// テンプレートパラメータを生成
		$breadcrumb_info = array();
		foreach($px->site()->get_breadcrumb_array() as $item){
			$breadcrumb_info[] = $px->site()->get_page_info($item);
		}

		$bros_info = array();
		foreach($px->site()->get_bros(null, array('filter' => false,)) as $item){
			$bros_info[] = $px->site()->get_page_info($item);
		}

		$children_info = array();
		foreach($px->site()->get_children(null, array('filter' => false,)) as $item){
			$children_info[] = $px->site()->get_page_info($item);
		}

		$global_menu = $px->site()->get_global_menu();
		$global_menu_info = array();
		foreach($global_menu as $page_id){
			array_push($global_menu_info, $px->site()->get_page_info($page_id));
		}

		$shoulder_menu = $px->site()->get_shoulder_menu();
		$shoulder_menu_info = array();
		foreach($shoulder_menu as $page_id){
			array_push($shoulder_menu_info, $px->site()->get_page_info($page_id));
		}

		$category_top = $px->site()->get_category_top();
		$category_top_info = false;
		if( $category_top !== false ){
			$category_top_info = $px->site()->get_page_info($category_top);
		}

		$category_sub_menu = $px->site()->get_children($category_top);
		$category_sub_menu_info = array();
		foreach($category_sub_menu as $page_id){
			array_push($category_sub_menu_info, $px->site()->get_page_info($page_id));
		}

		$extraValues = (object) array(
			'config' => $px->conf(),
			'topPageInfo' => $px->site()->get_page_info('') ?? (object) array(),
			'pageInfo' => $px->site()->get_current_page_info() ?? (object) array(),
			'breadcrumb' => $breadcrumb_info ?? array(),
			'parent' => $px->site()->get_page_info($px->site()->get_parent()) ?? (object) array(),
			'bros' => $bros_info ?? array(),
			'children' => $children_info ?? array(),
			'globalMenu' => $global_menu_info ?? array(),
			'shoulderMenu' => $shoulder_menu_info ?? array(),
			'categoryTop' => $category_top_info ?? array(),
			'categorySubMenu' => $category_sub_menu_info ?? array(),
		);

		$src = $px->bowl()->pull( 'main' );

		$kaleflower = new \kaleflower\kaleflower();
		$kflowResult = $kaleflower->build(
			'./'.$px->get_path_content(),
			array(
				'assetsPrefix' => './kflow'.$tmp_hash.'_files/resources/',
				'extra' => $extraValues,
			)
		);

		// --------------------------------------
		// HTMLを出力する
		foreach($kflowResult->html as $key => $src){
			if( count($kflowResult->assets ?? array()) ){
				// アセットのパスを置換する
				foreach($kflowResult->assets as $asset){
					$src = preg_replace('/'.preg_quote($asset->path, '/').'/s', $px->path_files_cache('/resources/'.basename($asset->path)), $src);
				}
			}
			$src = $utils->bindTwig($src, $extraValues);
			$px->bowl()->replace( $src, $key );
		}

		// --------------------------------------
		// CSSを出力する
		$realpath_css = $px->fs()->get_realpath($realpath_files_base.'/style.css');
		if( strlen($kflowResult->css ?? '') ){
			if(!is_file($realpath_css) || md5_file($realpath_css) !== md5($kflowResult->css)){
				$px->fs()->mkdir_r(dirname($realpath_css));
				$px->fs()->save_file($realpath_css, $kflowResult->css);
			}
			$px->bowl()->replace( '<link rel="stylesheet" href="'.htmlspecialchars($px->path_files_cache('/style.css')).'" />', 'head' );
		}elseif(is_file($realpath_css)){
			$px->fs()->rm($realpath_css);
		}

		// --------------------------------------
		// JSを出力する
		$realpath_js = $px->fs()->get_realpath($realpath_files_base.'/script.js');
		if( strlen($kflowResult->js ?? '') ){
			if(!is_file($realpath_js) || md5_file($realpath_js) !== md5($kflowResult->js)){
				$px->fs()->mkdir_r(dirname($realpath_js));
				$px->fs()->save_file($realpath_js, $kflowResult->js);
			}
			$px->bowl()->replace( '<script src="'.htmlspecialchars($px->path_files_cache('/script.js')).'"></script>', 'foot' );
		}elseif(is_file($realpath_js)){
			$px->fs()->rm($realpath_js);
		}

		// --------------------------------------
		// アセットを出力する
		$asset_basename_list = array();
		if( count($kflowResult->assets ?? array()) ){
			foreach($kflowResult->assets as $asset){
				$asset_basename_list[basename($asset->path)] = true;
				$realpath_asset = $px->realpath_files_cache('/resources/'.basename($asset->path));
				if(!is_file($realpath_asset) || md5_file($realpath_asset) !== md5(base64_decode($asset->base64))){
					$px->fs()->mkdir_r(dirname($realpath_asset));
					$px->fs()->save_file($realpath_asset, base64_decode($asset->base64));
				}
			}
		}

		// 未定義のアセットを削除
		$realpath_asset_dir = $px->realpath_files_cache('/resources/');
		$file_list = $px->fs()->ls($realpath_asset_dir);
		if( is_array($file_list) && count($file_list) ){
			foreach($file_list as $file_basename){
				if( !($asset_basename_list[$file_basename] ?? null) ){
					$px->fs()->rm($realpath_asset_dir.$file_basename);
				}
			}
		}

		return true;
	}
}
