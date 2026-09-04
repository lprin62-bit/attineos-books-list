<?php
/**
 * Search and language filter.
 *
 * A plain GET form: it works without JavaScript, keeps the query in the URL
 * so a result page can be bookmarked and shared, and leaves the browser's
 * back button meaningful.
 *
 * No `action` attribute: a form without one submits to the current URL,
 * which is correct in every context — subdirectory install, plain
 * permalinks, page displayed outside the loop.
 *
 * @package BooksList
 *
 * @var array{hidden:array<string,string>,search:string,language:string,languages:array<string,string>} $books_list_form Form data.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$books_list_form_id = wp_unique_id( 'books-list-form-' );
?>
<form class="books-list__form" method="get" role="search">

	<?php foreach ( $books_list_form['hidden'] as $books_list_key => $books_list_value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $books_list_key ); ?>" value="<?php echo esc_attr( $books_list_value ); ?>" />
	<?php endforeach; ?>

	<p class="books-list__field">
		<label for="<?php echo esc_attr( $books_list_form_id ); ?>-search">
			<?php esc_html_e( 'Search by title', 'books-list' ); ?>
		</label>
		<input
			type="search"
			id="<?php echo esc_attr( $books_list_form_id ); ?>-search"
			name="<?php echo esc_attr( \BooksList\Renderer::SEARCH_PARAM ); ?>"
			value="<?php echo esc_attr( $books_list_form['search'] ); ?>" />
	</p>

	<p class="books-list__field">
		<label for="<?php echo esc_attr( $books_list_form_id ); ?>-language">
			<?php esc_html_e( 'Language', 'books-list' ); ?>
		</label>
		<select
			id="<?php echo esc_attr( $books_list_form_id ); ?>-language"
			name="<?php echo esc_attr( \BooksList\Renderer::LANGUAGE_PARAM ); ?>">
			<option value=""><?php esc_html_e( 'All languages', 'books-list' ); ?></option>
			<?php foreach ( $books_list_form['languages'] as $books_list_code => $books_list_label ) : ?>
				<option
					value="<?php echo esc_attr( $books_list_code ); ?>"
					<?php selected( $books_list_code, $books_list_form['language'] ); ?>>
					<?php echo esc_html( $books_list_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p class="books-list__field">
		<button type="submit"><?php esc_html_e( 'Search', 'books-list' ); ?></button>
	</p>

</form>