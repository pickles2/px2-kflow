<?php
namespace pickles2\px2kflow;

/**
 * px2-kflow
 */
class kflow{

	/**
	 * kflow変換処理の実行
	 * @param object $px Picklesオブジェクト
	 * @param object $plugin_options プラグイン設定
	 */
	public static function processor( $px, $plugin_options ){
		$plugin_options = (object) $plugin_options;
		$tmp_hash = substr(md5(microtime()), 0, 16);
		$realpath_files_base = $px->realpath_files_cache();

		$src = $px->bowl()->pull( 'main' );

		$kaleflower = new \kaleflower\kaleflower();

		$kflowResult = $kaleflower->build(
			'./'.$px->get_path_content(),
			array(
				'assetsPrefix' => './kflow'.$tmp_hash.'_files/resources/',
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
			$px->bowl()->replace( $src, $key );
		}

		// --------------------------------------
		// CSSを出力する
		$realpath_css = $px->fs()->get_realpath($realpath_files_base.'/style.css');
		if( strlen($kflowResult->css ?? '') ){
			if(!is_file($realpath_css) || md5_file($realpath_css) !== md5($kflowResult->css)){
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
		foreach($file_list as $file_basename){
			if( !($asset_basename_list[$file_basename] ?? null) ){
				$px->fs()->rm($realpath_asset_dir.$file_basename);
			}
		}

		return true;
	}
}
