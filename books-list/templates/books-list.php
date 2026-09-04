<?php
/**
 * Book list, or the notice shown when there is nothing to list.
 *
 * @package BooksList
 *
 * @var array{heading:string,form:array,books?:\BooksList\Book[],message?:string,pagination?:string} $context Template data.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$books_list_id = wp_unique_id( 'books-list-' );
?>
<section class="books-list alignwide" aria-labelledby="<?php echo esc_attr( $books_list_id ); ?>">

	<h2 id="<?php echo esc_attr( $books_list_id ); ?>"><?php echo esc_html( $context['heading'] ); ?></h2>

	<?php
	$books_list_form = $context['form'];
	require __DIR__ . '/search-form.php';
	?>

	<?php if ( isset( $context['message'] ) ) : ?>

		<p role="status"><?php echo esc_html( $context['message'] ); ?></p>

	<?php else : ?>

		<ul class="books-list__grid">
			<?php foreach ( $context['books'] as $books_list_book ) : ?>
				<li><?php include __DIR__ . '/book-card.php'; ?></li>
			<?php endforeach; ?>
		</ul>

		<?php if ( '' !== $context['pagination'] ) : ?>
			<nav class="books-list__pagination" aria-label="<?php esc_attr_e( 'Book list pages', 'books-list' ); ?>">
				<?php echo wp_kses_post( $context['pagination'] ); ?>
			</nav>
		<?php endif; ?>

	<?php endif; ?>

</section>