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

		foreach( $px->bowl()->get_keys() as $key ){
			$src = $px->bowl()->pull( $key );

			// TODO: kflow変換処理

			$px->bowl()->replace( $src, $key );
		}

		return true;
	}
}
