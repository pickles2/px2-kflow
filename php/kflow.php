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
					$src = preg_replace('/'.preg_quote($asset->path, '/').'/s', $px->path_files('/resources/'.basename($asset->path)), $src);
				}
			}
			$px->bowl()->replace( $src, $key );
		}

		// --------------------------------------
		// CSSを出力する
		if( strlen($kflowResult->css ?? '') ){
			$realpath_css = $px->fs()->get_realpath($px->realpath_files('/style.css'));
			if(!is_file($realpath_css)){
				$px->fs()->save_file($realpath_css, $kflowResult->css);
			}
			$px->bowl()->replace( '<link rel="stylesheet" href="'.htmlspecialchars($px->path_files('/style.css')).'" />', 'head' );
		}

		// --------------------------------------
		// JSを出力する
		if( strlen($kflowResult->js ?? '') ){
			$realpath_css = $px->fs()->get_realpath($px->realpath_files('/script.js'));
			if(!is_file($realpath_css)){
				$px->fs()->save_file($realpath_css, $kflowResult->js);
			}
			$px->bowl()->replace( '<script src="'.htmlspecialchars($px->path_files('/script.js')).'"></script>', 'foot' );
		}

		// --------------------------------------
		// アセットを出力する
		if( count($kflowResult->assets ?? array()) ){
			foreach($kflowResult->assets as $asset){
				$realpath_asset = $px->realpath_files('/resources/'.basename($asset->path));
				if(!is_file($realpath_asset)){
					$px->fs()->mkdir_r(dirname($realpath_asset));
					$px->fs()->save_file($realpath_asset, base64_decode($asset->base64));
				}
			}
		}

		return true;
	}
}
