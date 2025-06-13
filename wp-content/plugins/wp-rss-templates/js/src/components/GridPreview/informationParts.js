import { format as phpDateFormat } from '~/utils/phpDateFormat'
import distanceInWordsToNow  from 'date-fns/distance_in_words_to_now'
import { maybeWrapInAnchor } from './utils'

export default {
  date: {
    render () {
      const date = new Date(this.item.timestamp * 1000)

      return <div key={'date'}>
        { this.options.date_prefix + ' ' }
        { this.options.date_use_time_ago ?
          distanceInWordsToNow(date, {addSuffix: true})
          :
          phpDateFormat(date, this.options.date_format) }
      </div>
    }
  },
  source: {
    render (h) {
      return <div key={'source'}>
        { this.options.source_prefix + ' ' }
        { maybeWrapInAnchor(this.item.source.title, this.item.source.site_url, (!this.options.item_is_link) && this.options.source_is_link, h) }
      </div>
    }
  },
  author: {
    render () {
      return <div key={'author'}>
        { this.options.author_prefix + ' ' }
        { this.item.author }
      </div>
    }
  },
}
