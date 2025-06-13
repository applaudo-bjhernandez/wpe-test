export function maybeWrapInAnchor (text, link, isWrapped, h) {
  if (!isWrapped) {
    return text
  }
  return <a href={link} onClick={e => e.preventDefault()}>{ text }</a>
}
