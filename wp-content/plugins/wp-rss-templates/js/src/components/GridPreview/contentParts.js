import informationParts from './informationParts'
import { maybeWrapInAnchor } from '~/components/GridPreview/utils'

/**
 * Text truncate helper.
 *
 * @param str
 * @param length
 *
 * @return {string}
 */
const truncate = (str, length) => length > 0 ? str.substring(0, length).trim() + '…' : str

/**
 * Limit string by words.
 *
 * @param {string} str
 * @param {int} words
 * @param {string} ending
 *
 * @return {string}
 */
const wordLimit = (str, words, ending) => {
  if (words <= 0) {
    return str
  }
  const wordsStrings = str.split(' ')
  if (wordsStrings.length < words) {
    return str
  }
  return wordsStrings.slice(0, words).join(' ') + ending
}

/**
 * Render objects according the order object (names are keys and order is a value).
 *
 * @param {Object.<string, Object>} partsObject
 * @param {Object.<string, number>} orderObject
 * @param {Function} filterFunction Whether the item should be visible.
 * @param {Function} h
 *
 * @return {VNode[]}
 */
function renderObjects (partsObject, orderObject, filterFunction, { h }) {
  return Object.keys(partsObject)
    .sort((a, b) => orderObject[a] - orderObject[b])
    .filter(filterFunction)
    .map(key => {
      return partsObject[key].render.call(this, h)
    })
}

export default {
  title: {
    render (h) {
      const title = truncate(this.item.title, this.options.title_max_length)
      return <div class="wpra-grid-item__item wpra-grid-item__title">
        { maybeWrapInAnchor(title, this.item.permalink, !this.options.item_is_link, h) }
      </div>
    }
  },
  image: {
    render (h) {
      if (this.options.image_is_background) {
        return null
      }

      const imageBackgroundStyle = {
        backgroundImage: `url(${this.item.image})`,
        height: this.options.thumbnail_height + 'px',
      }

      const wrapper = this.options.thumbnail_is_link ? 'a' : 'div'
      const classname = {
        'wpra-grid-item__item': true,
        'wpra-grid-item__image': true,
      };

      return h(wrapper, {
        class: classname,
        style: imageBackgroundStyle
      }, [
        this.renderParts(item => item.target === 'image', this.options.card_fields_order, h)
      ])
    }
  },
  excerpt: {
    render () {
      const readMore = this.options.excerpt_more_enabled && (!this.options.item_is_link) ?
        <a href={this.item.permalink} onClick={e => e.preventDefault()}>{ this.options.excerpt_read_more }</a> : null

      return <div class="wpra-grid-item__item wpra-grid-item__excerpt">
        { wordLimit(this.item.excerpt, this.options.excerpt_max_words, this.options.excerpt_ending) + ' ' }
        { readMore }
      </div>
    }
  },
  audio: {
    render () {
      return WpraTemplates.audio_features_enabled && this.options.audio_player_enabled && (
          <div class="wpra-feed-audio">
            <audio controls>
              <source src={this.item.audio_url} type="audio/mp3" />
              Your browser does not support HTML5 audio players.
            </audio>
        </div>
      );
    }
  },
  information: {
    render (h) {
      const className = {
        'wpra-grid-item__item': true,
        'wpra-grid-item__information': true,
        'block': this.options.info_item_block,
      }
      return <div class={className}>
        {
          renderObjects.call(
            this,
            informationParts,
            this.options.information_fields_order,
            (item) => {
              const key = `${item}_enabled`
              return !this.options.hasOwnProperty(key) || !!this.options[key]
            },
            { h }
          )
        }
      </div>
    }
  },
}
