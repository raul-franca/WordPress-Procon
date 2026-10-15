<?php
/**
 * Walker do menu principal: gera o mesmo markup da página de referência
 * (li.nav-item > a.nav-link | button.nav-link + ul.dropdown-menu).
 *
 * Para criar um separador no submenu, adicione a classe CSS "dropdown-divider"
 * ao item (Aparência > Menus > Opções de tela > Classes CSS).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Procon_Nav_Walker extends Walker_Nav_Menu {

	/** Título do item pai, usado no aria-label do submenu. */
	private $parent_title = '';

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= sprintf(
			'<ul class="dropdown-menu" role="menu" aria-label="%s">',
			esc_attr( 'Submenu ' . $this->parent_title )
		);
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = (array) $item->classes;
		$title   = apply_filters( 'the_title', $item->title, $item->ID );

		if ( $depth > 0 ) {
			if ( in_array( 'dropdown-divider', $classes, true ) ) {
				$output .= '<li class="dropdown-divider" role="separator">';
				return;
			}
			$output .= '<li>' . $this->link( $item, $title, '', 'menuitem' );
			return;
		}

		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$active       = array_intersect( $classes, array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent', 'current_page_parent' ) );

		$output .= '<li class="nav-item">';

		if ( $has_children ) {
			$this->parent_title = $title;
			$output            .= sprintf(
				'<button class="nav-link%s" type="button" role="menuitem" aria-haspopup="true" aria-expanded="false">%s <span class="caret" aria-hidden="true">▾</span></button>',
				$active ? ' active' : '',
				esc_html( $title )
			);
			return;
		}

		$output .= $this->link( $item, $title, 'nav-link' . ( $active ? ' active' : '' ), 'menuitem' );
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}

	private function link( $item, $title, $class, $role ) {
		$attrs = array(
			'href'  => $item->url,
			'class' => $class,
			'role'  => $role,
		);
		if ( $item->target ) {
			$attrs['target'] = $item->target;
			$attrs['rel']    = 'noopener';
		}
		if ( in_array( 'current-menu-item', (array) $item->classes, true ) ) {
			$attrs['aria-current'] = 'page';
		}

		$html = '<a';
		foreach ( $attrs as $name => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$html .= sprintf( ' %s="%s"', $name, 'href' === $name ? esc_url( $value ) : esc_attr( $value ) );
		}
		return $html . '>' . esc_html( $title ) . '</a>';
	}
}
