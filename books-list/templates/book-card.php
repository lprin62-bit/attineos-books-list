<?php
/**
 * A single book card.
 *
 * @package BooksList
 *
 * @var \BooksList\Book $books_list_book The book being rendered.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$books_list_authors   = implode( ', ', $books_list_book->authors );
$books_list_languages = strtoupper( implode( ', ', $books_list_book->languages ) );
?>
<article class="books-list__card">

	<?php if ( null !== $books_list_book->cover_url ) : ?>
		<img class="books-list__cover" src="<?php echo esc_url( $books_list_book->cover_url ); ?>" alt="" loading="lazy" />
	<?php else : ?>
		<div class="books-list__cover" aria-hidden="true"></div>
	<?php endif; ?>

	<h3>
		<?php if ( null !== $books_list_book->source_url ) : ?>
			<a href="<?php echo esc_url( $books_list_book->source_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $books_list_book->title ); ?>
				<span class="books-list__sr"><?php esc_html_e( '(opens in a new tab)', 'books-list' ); ?></span>
			</a>
		<?php else : ?>
			<?php echo esc_html( $books_list_book->title ); ?>
		<?php endif; ?>
	</h3>

	<dl class="books-list__meta">
		<dt><?php esc_html_e( 'Author', 'books-list' ); ?></dt>
		<dd><?php echo esc_html( '' !== $books_list_authors ? $books_list_authors : __( 'Unknown author', 'books-list' ) ); ?></dd>

		<dt><?php esc_html_e( 'Language', 'books-list' ); ?></dt>
		<dd><?php echo esc_html( '' !== $books_list_languages ? $books_list_languages : __( 'Not specified', 'books-list' ) ); ?></dd>

		<dt><?php esc_html_e( 'Downloads', 'books-list' ); ?></dt>
		<dd><?php echo esc_html( number_format_i18n( $books_list_book->download_count ) ); ?></dd>
	</dl>

</article>