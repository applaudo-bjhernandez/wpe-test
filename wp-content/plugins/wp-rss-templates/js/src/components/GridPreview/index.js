import contentParts from './contentParts'

let parts = contentParts

/**
 * Component for grid item preview.
 */
export default {
  inject: [
    'hooks',
  ],
  data () {
    return {
      /*
       * Inject it as a property, or from some global.
       *
       * @todo
       */
      item: {
        title: 'Lorem Ipsum Dolor Sit Amet',
        permalink: 'https://www.wprssaggregator.com/core-version-4-13-celebrating-one-million-downloads/',
        excerpt: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur hendrerit lectus pellentesque justo lobortis, non luctus ante dictum. Nulla at elementum urna. Aenean molestie libero orci, eget ultrices felis vulputate in. Mauris commodo quis ante in vulputate.',
        image: WpraTemplates.preview.image,
        timestamp: (new Date().getTime() / 1000) - 3600,
        source: {
          title: 'RSS Feed',
          site_url: 'https://www.wprssaggregator.com/',
        },
        author: 'Mark Zahra',
        audio_url: '',
      }
    }
  },
  props: {
    /**
     * Template's model options.
     *
     * @property {Object} options
     */
    options: {
      type: Object,
      required: true,
    },
  },
  created () {
    parts = this.hooks.apply('grid-preview-content', this, parts, {
      h: this.$createElement
    })
  },
  methods: {
    /**
     * Render content parts that passes preferred filter.
     *
     * @param {Function} filterFn Function for filtering parts according their position.
     * @param {Object.<string, number>} orderObject
     * @param {Function} h Render function.
     *
     * @return {VNode[]}
     */
    renderParts (filterFn, orderObject, h) {
      return Object.keys(parts)
        .filter(key => filterFn(parts[key]))
        .filter(key => {
          const visibleKey = `show_${key}`
          return !this.options.hasOwnProperty(visibleKey) || !!this.options[visibleKey]
        })
        .sort((a, b) => orderObject[a] - orderObject[b])
        .map(key => this.renderPart(key, h))
    },

    /**
     * Render a content part of the template according passed options.
     *
     * @param {string} name The name of a content part.
     * @param {Function} h Render function.
     *
     * @return {VNode}
     */
    renderPart (name, h) {
      return this.hooks.apply(
        `grid-preview-item-${name}`,
        this,
        parts[name].render.call(this, h),
        { h }
      )
    }
  },
  render (h) {
    /*
     * Grid item's image.
     */
    const imageBackgroundStyle = this.options.image_is_background ? {
      backgroundImage: `url(${this.item.image})`,
      height: this.options.thumbnail_height + 'px'
    } : {}

    /**
     * Class names for grid item preview.
     */
    const classNames = {
      'wpra-grid-item': true,
      'wpra-grid-item--link': this.options.item_is_link,
      'wpra-grid-item--image-background': this.options.image_is_background,
      'wpra-grid-item--pull-last-item': this.options.latest_to_bottom,
      'wpra-grid-item--fill-image': this.options.fill_image,
      'wpra-grid-item--no-borders': !this.options.show_borders,
    }

    return <div class={classNames} style={imageBackgroundStyle}>
      <div class="wpra-grid-item__content">
        { this.renderParts(item => !item.target || item.target === 'content', this.options.card_fields_order, h) }
      </div>
    </div>
  }
}
