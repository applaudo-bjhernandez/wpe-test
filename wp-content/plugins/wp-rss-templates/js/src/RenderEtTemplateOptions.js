/**
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
export default function RenderEtTemplateOptions (h, {Input, hooks}) {
  return <div>
    <div>
      <Input
        type='checkbox'
        label={'Enable excerpts'}
        value={this.model.options.show_excerpt}
        onInput={(e) => this.model.options.show_excerpt = e}
        title={this.tooltips.options.show_excerpt}
        style={{paddingTop: '20px', fontWeight: 'bold'}}
      />
      <Input
        type='number'
        label={'Excerpts word limit'}
        value={this.model.options.excerpt_max_words}
        onInput={(e) => this.model.options.excerpt_max_words = e}
        title={this.tooltips.options.excerpt_max_words}
        disabled={!this.model.options.show_excerpt}
      />
      <Input
        type='text'
        label={'Excerpts ending'}
        value={this.model.options.excerpt_ending}
        onInput={(e) => this.model.options.excerpt_ending = e}
        title={this.tooltips.options.excerpt_ending}
        disabled={!this.model.options.show_excerpt}
      />
      <Input
        type='checkbox'
        label={'Enable "Read more" link'}
        value={this.model.options.excerpt_more_enabled}
        onInput={(e) => this.model.options.excerpt_more_enabled = e}
        title={this.tooltips.options.excerpt_more_enabled}
        disabled={!this.model.options.show_excerpt}
      />
      <Input
        type='text'
        label={'"Read more" text'}
        value={this.model.options.excerpt_read_more}
        onInput={(e) => this.model.options.excerpt_read_more = e}
        title={this.tooltips.options.excerpt_read_more}
        disabled={!this.model.options.show_excerpt || !this.model.options.excerpt_more_enabled}
      />
    </div>
    <div>
      <Input
        type='checkbox'
        label={'Enable thumbnails'}
        value={this.model.options.show_image}
        onInput={(e) => this.model.options.show_image = e}
        title={this.tooltips.options.show_image}
        style={{paddingTop: '20px', fontWeight: 'bold'}}
      />
      <Input
        type='select'
        label={'Thumbnail placement'}
        value={this.model.options.thumbnail_placement}
        onInput={(e) => this.model.options.thumbnail_placement = e}
        title={this.tooltips.options.thumbnail_placement}
        options={WpraTemplates.options.thumbnail_placement}
        disabled={!this.model.options.show_image}
      />
      <Input
        type='number'
        label={'Thumbnail image width'}
        value={this.model.options.thumbnail_width}
        onInput={(e) => this.model.options.thumbnail_width = e}
        title={this.tooltips.options.thumbnail_width}
        disabled={!this.model.options.show_image}
      />
      <Input
        type='number'
        label={'Thumbnail image height'}
        value={this.model.options.thumbnail_height}
        onInput={(e) => this.model.options.thumbnail_height = e}
        title={this.tooltips.options.thumbnail_height}
        disabled={!this.model.options.show_image}
      />
      <Input
        type='checkbox'
        label={'Link thumbnail to permalink'}
        value={this.model.options.thumbnail_is_link}
        onInput={(e) => this.model.options.thumbnail_is_link = e}
        title={this.tooltips.options.thumbnail_is_link}
        disabled={!this.model.options.show_image}
      />
      <Input
        type='select'
        label={'When feed item has no thumbnail'}
        options={WpraTemplates.options.empty_thumbnail_behavior}
        value={this.model.options.empty_thumbnail_behavior}
        onInput={(e) => this.model.options.empty_thumbnail_behavior = e }
        title={this.tooltips.options.empty_thumbnail_behavior}
        disabled={!this.model.options.show_image}
      />
    </div>
  </div>
}
