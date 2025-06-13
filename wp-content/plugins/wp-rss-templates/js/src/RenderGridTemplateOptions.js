import SettingsGroup from '~/components/SettingsGroup';
import SortableGroup from '~/components/SortableGroup';
import {arrayToOrderObject, orderObjectToArray} from '~/utils/orderObject';
import GridPreview from '~/components/GridPreview';

let informationSettings = {
  date(h, index, Input) {
    return <SettingsGroup
        title={'Date'}
        sortable={true}
        value={this.model.options.date_enabled}
        onInput={value => this.model.options.date_enabled = value}
        index={index}
        key="date"
    >
      <Input type="text"
             label={'Date prefix'}
             value={this.model.options.date_prefix}
             onInput={(e) => this.model.options.date_prefix = e}
             disabled={!this.model.options.date_enabled}
             title={this.tooltips.options.date_prefix}
      />
      <Input type="text"
             label={'Date format'}
             value={this.model.options.date_format}
             onInput={(e) => this.model.options.date_format = e}
             disabled={this.model.options.date_use_time_ago || !this.model.options.date_enabled}
             title={this.tooltips.options.date_format}
      />
      <Input type="checkbox"
             label={'Use "time ago" format'}
             description={'Example: 20 minutes ago'}
             value={this.model.options.date_use_time_ago}
             onInput={(e) => this.model.options.date_use_time_ago = e}
             disabled={!this.model.options.date_enabled}
             title={this.tooltips.options.date_use_time_ago}
      />
    </SettingsGroup>
  },
  author (h, index, Input) {
    return <SettingsGroup
      title={'Author'}
      sortable={true}
      value={this.model.options.author_enabled}
      onInput={value => this.model.options.author_enabled = value}
      index={index}
      key="author"
    >
      <Input type="text"
             label={'Author prefix'}
             value={this.model.options.author_prefix}
             onInput={(e) => this.model.options.author_prefix = e}
             disabled={!this.model.options.author_enabled}
             title={this.tooltips.options.author_prefix}
      />
    </SettingsGroup>
  },
  source (h, index, Input) {
    return <SettingsGroup
      title={'Source'}
      sortable={true}
      value={this.model.options.source_enabled}
      onInput={value => this.model.options.source_enabled = value}
      help={this.tooltips.options.source_enabled}
      index={index}
      key="source"
    >
      <Input type="text"
             label={'Source prefix'}
             value={this.model.options.source_prefix}
             onInput={(e) => this.model.options.source_prefix = e}
             disabled={!this.model.options.source_enabled}
             title={this.tooltips.options.source_prefix}
      />
      <Input type="checkbox"
             label={'Link source name'}
             value={this.model.options.source_is_link}
             onInput={(e) => this.model.options.source_is_link = e}
             title={this.tooltips.options.source_is_link}
             disabled={!this.model.options.source_enabled || (this.model.options.item_is_link)}
             description={
               !this.model.options.item_is_link ? null : '<div class="disable-ignored">The source cannot be a link when the whole grid item is clickable. To make source clickable uncheck "Make whole grid item clickable" setting.</div>'
             }
      />
    </SettingsGroup>
  },
}

let makeCardSettings = function (informationSettings) {
  return {
    image: {
      render (h, index, Input) {
        return <SettingsGroup
          title={'Image'}
          description={this.model.options.image_is_background ? 'Image reordering has no effect when background mode is enabled' : null}
          sortable={true}
          value={this.model.options.show_image}
          onInput={value => this.model.options.show_image = value}
          index={index}
          key="image"
        >
          <Input
            type="checkbox"
            label={'Use image as a background'}
            value={this.model.options.image_is_background}
            onInput={(e) => this.model.options.image_is_background = e}
            title={this.tooltips.options.image_is_background}
            disabled={!this.model.options.show_image}
          />
          <Input
            type="checkbox"
            label={'Link the image'}
            value={this.model.options.thumbnail_is_link}
            onInput={(e) => this.model.options.thumbnail_is_link = e}
            title={this.tooltips.options.thumbnail_is_link}
            disabled={!this.model.options.show_image || this.model.options.item_is_link || this.model.options.image_is_background}
            description={
              (this.model.options.item_is_link || this.model.options.image_is_background) ? '<div class="disable-ignored">This option is not applicable when the image is a background or when the whole grid item is clickable.</div>' : null
            }
          />
          <Input
            type="number"
            min="0"
            label={'Image height (pixels)'}
            value={this.model.options.thumbnail_height}
            onInput={(e) => this.model.options.thumbnail_height = e}
            title={this.tooltips.options.thumbnail_height}
            disabled={!this.model.options.show_image}
          />
          <Input
            type="checkbox"
            label={'Fill image to size'}
            value={this.model.options.fill_image}
            onInput={(e) => this.model.options.fill_image = e}
            title={this.tooltips.options.fill_image}
            disabled={!this.model.options.show_image}
          />
          <Input
            type="checkbox"
            label={'Replace with embed (if available)'}
            value={this.model.options.videos_enabled}
            onInput={(e) => this.model.options.videos_enabled = e}
            title={this.tooltips.options.videos_enabled}
            disabled={!this.model.options.show_image || this.model.options.image_is_background}
            description={
                '<div>Currently only supports YouTube videos</div>'
            }
          />
        </SettingsGroup>
      }
    },
    information: {
      render (h, index, Input) {
        return <SettingsGroup
          title={'Information'}
          sortable={true}
          initialOpen={true}
          value={this.model.options.show_information}
          onInput={value => this.model.options.show_information = value}
          index={index}
          key="information"
        >
          <Input
            type="checkbox"
            label={'Display each item on separate line'}
            value={this.model.options.info_item_block}
            onInput={(e) => this.model.options.info_item_block = e}
            title={this.tooltips.options.info_item_block}
          />
          <SortableGroup
            for="information"
            value={orderObjectToArray(this.model.options.information_fields_order)}
            onInput={value => this.model.options.information_fields_order = arrayToOrderObject(value)}
          >
            {
              orderObjectToArray(this.model.options.information_fields_order).map((item, index) => {
                return informationSettings[item].call(this, h, index, Input)
              })
            }
          </SortableGroup>
        </SettingsGroup>
      }
    },
    excerpt: {
      render (h, index, Input) {
        return <SettingsGroup
          title={'Excerpt'}
          sortable={true}
          value={this.model.options.show_excerpt}
          onInput={value => this.model.options.show_excerpt = value}
          index={index}
          key="excerpt"
        >
          <Input
            type="number"
            label={'Excerpts word limit'}
            value={this.model.options.excerpt_max_words || ''}
            placeholder={'No limit'}
            onInput={(e) => this.model.options.excerpt_max_words = e}
            title={this.tooltips.options.excerpt_max_words}
          />
          <Input
            type='text'
            label={'Excerpts ending'}
            value={this.model.options.excerpt_ending}
            onInput={(e) => this.model.options.excerpt_ending = e}
            title={this.tooltips.options.excerpt_ending}
          />
          <Input
            type="checkbox"
            label={'Enable "Read more" link'}
            value={this.model.options.excerpt_more_enabled}
            onInput={(e) => this.model.options.excerpt_more_enabled = e}
            title={this.tooltips.options.excerpt_more_enabled}
            disabled={this.model.options.item_is_link || !this.model.options.show_excerpt}
            description={
              !this.model.options.item_is_link ? null : '<div class="disable-ignored">The excerpt cannot contain a link when the whole grid item is clickable. To enable "read more" uncheck "Make whole grid item clickable" setting.</div>'
            }
          />
          <Input
            type="text"
            label={'"Read more" text'}
            value={this.model.options.excerpt_read_more}
            onInput={(e) => this.model.options.excerpt_read_more = e}
            title={this.tooltips.options.excerpt_read_more}
            disabled={!this.model.options.show_excerpt || !this.model.options.excerpt_more_enabled || this.model.options.item_is_link}
          />
        </SettingsGroup>
      }
    },
    title: {
      render (h, index, Input) {
        return <SettingsGroup
          title={'Title'}
          sortable={true}
          value={this.model.options.show_title}
          onInput={value => this.model.options.show_title = value}
          index={index}
          key="title"
        >
          <Input
              type="number"
              label={'Title maximum length'}
              value={this.model.options.title_max_length || ''}
              placeholder={'No limit'}
              onInput={(e) => this.model.options.title_max_length = e}
              title={this.tooltips.options.title_max_length}
          />
        </SettingsGroup>
      }
    },
    audio: {
      render(h, index, Input) {
        return WpraTemplates.audio_features_enabled &&
            <SettingsGroup
                title="Audio Player"
                sortable={true}
                value={this.model.options.audio_player_enabled}
                onInput={value => this.model.options.audio_player_enabled = value}
                index={index}
                canOpen={false}
                key="audio"
            />;
      }
    },
  }
}

/**
 *
 * @param {Function} h
 * @param {Function} Input
 * @param {HooksService} hooks
 *
 * @property {TemplateModel} model
 * @property {TemplateModel} tooltips
 *
 * @return {*}
 *
 * @constructor
 */
export default function RenderGridTemplateOptions (h, {Input, hooks}) {
  informationSettings = hooks.apply('grid-information-settings-parts', this, informationSettings);

  let cardSettings = hooks.apply('grid-settings-parts', this, makeCardSettings(informationSettings));
  let orderedKeys = orderObjectToArray(this.model.options.card_fields_order);

  const settingKeys = Object.keys(cardSettings);
  const missingKeys = settingKeys.filter(i => !orderedKeys.includes(i));
  orderedKeys = orderedKeys.concat(missingKeys);

  return (
      <div style={{paddingTop: '6px'}} class="wpra-grid-editor">
        <div class="wpra-grid-editor__preview">
          <h3>Preview</h3>
          <GridPreview options={this.model.options} />
          <div style={{paddingTop: '1.25rem'}}>
            <Input type="checkbox"
                   label={'Show borders'}
                   value={this.model.options.show_borders}
                   onInput={(e) => this.model.options.show_borders = e}
                   title={this.tooltips.options.show_borders}
            />
            <Input type="checkbox"
                   label={'Make whole grid item clickable'}
                   value={this.model.options.item_is_link}
                   onInput={(e) => this.model.options.item_is_link = e}
                   title={this.tooltips.options.item_is_link}
            />

            <Input type="checkbox"
                   label={'Align last element to bottom'}
                   value={this.model.options.latest_to_bottom}
                   onInput={(e) => this.model.options.latest_to_bottom = e}
                   title={this.tooltips.options.latest_to_bottom}
            />
            <Input type="number"
                   class="form-input--vertical"
                   label={'Max number of columns'}
                   min={1}
                   value={this.model.options.columns_number}
                   onInput={(e) => this.model.options.columns_number = e}
                   title={this.tooltips.options.columns_number}
            />
            <Input type="number"
                   class="form-input--vertical"
                   label={'Number of items to show'}
                   value={this.model.options.limit || ''}
                   onInput={(e) => this.model.options.limit = e}
                   title={this.tooltips.options.limit}
            />

            <Input type="checkbox"
                   label={'Pagination'}
                   value={this.model.options.pagination}
                   onInput={(e) => this.model.options.pagination = e}
                   style={{paddingTop: '20px', fontWeight: 'bold'}}
                   title={this.tooltips.options.pagination}
            />
            <Input type="select"
                   label={'Pagination style'}
                   class="form-input--vertical"
                   options={WpraTemplates.options.pagination_type}
                   value={this.model.options.pagination_type}
                   onInput={(e) => this.model.options.pagination_type = e}
                   disabled={!this.model.options.pagination}
                   title={this.tooltips.options.pagination_type}
            />
          </div>
        </div>
        <div class="wpra-grid-editor__config">
          <SortableGroup
              for="card"
              value={orderedKeys}
              onInput={value => this.model.options.card_fields_order = arrayToOrderObject(value)}
          >
            {orderedKeys.map((item, index) => cardSettings[item].render.call(this, h, index, Input))}
          </SortableGroup>
        </div>
      </div>
  );
}
