import { Editor } from 'https://esm.sh/@tiptap/core@2.11.5'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2.11.5'
import Underline from 'https://esm.sh/@tiptap/extension-underline@2.11.5'
import Link from 'https://esm.sh/@tiptap/extension-link@2.11.5'
import TextAlign from 'https://esm.sh/@tiptap/extension-text-align@2.11.5'
import Image from 'https://esm.sh/@tiptap/extension-image@2.11.5'
import Placeholder from 'https://esm.sh/@tiptap/extension-placeholder@2.11.5'
import Table from 'https://esm.sh/@tiptap/extension-table@2.11.5'
import TableRow from 'https://esm.sh/@tiptap/extension-table-row@2.11.5'
import TableCell from 'https://esm.sh/@tiptap/extension-table-cell@2.11.5'
import TableHeader from 'https://esm.sh/@tiptap/extension-table-header@2.11.5'

const root = document.querySelector('[data-docs-editor]')
const bodyField = document.getElementById('docs-body')
const form = document.querySelector('[data-docs-form]')
const imageInput = document.getElementById('docs-image-input')
const initialEl = document.getElementById('docs-initial-content')
const cfg = window.ADHERI_DOCS || {}
const tableBar = document.querySelector('[data-table-bar]')
const tableInsert = document.querySelector('[data-table-insert]')
const tablePicker = document.querySelector('[data-table-picker]')
const tableGrid = document.querySelector('[data-table-grid]')
const tablePickerLabel = document.querySelector('[data-table-picker-label]')
const tablePickerBtn = document.querySelector('[data-command="tablePicker"]')

if (root && bodyField) {
  let initial = '<p></p>'
  try {
    initial = JSON.parse(initialEl?.textContent || '""') || '<p></p>'
  } catch {
    initial = '<p></p>'
  }
  if (!initial || !String(initial).trim()) initial = '<p></p>'

  const CustomTableCell = TableCell.extend({
    addAttributes() {
      return {
        ...this.parent?.(),
        backgroundColor: {
          default: null,
          parseHTML: (element) => element.getAttribute('data-background-color') || element.style.backgroundColor || null,
          renderHTML: (attributes) => {
            if (!attributes.backgroundColor) return {}
            return {
              'data-background-color': attributes.backgroundColor,
              style: `background-color: ${attributes.backgroundColor}`,
            }
          },
        },
      }
    },
  })

  const editor = new Editor({
    element: root,
    extensions: [
      StarterKit.configure({
        heading: { levels: [1, 2, 3] },
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
      }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Image.configure({ allowBase64: false }),
      Placeholder.configure({ placeholder: 'Start writing…' }),
      Table.configure({
        resizable: true,
        allowTableNodeSelection: true,
        HTMLAttributes: { class: 'docs-table' },
      }),
      TableRow,
      TableHeader,
      CustomTableCell,
    ],
    content: initial,
    editorProps: {
      attributes: {
        class: 'ProseMirror',
        spellcheck: 'true',
      },
      handleKeyDown: (view, event) => {
        // Tab / Shift+Tab move between cells like Google Docs
        if (event.key === 'Tab' && editor.isActive('table')) {
          event.preventDefault()
          if (event.shiftKey) {
            editor.commands.goToPreviousCell()
          } else {
            const moved = editor.commands.goToNextCell()
            if (!moved) {
              editor.chain().focus().addRowAfter().goToNextCell().run()
            }
          }
          return true
        }
        return false
      },
    },
    onSelectionUpdate: ({ editor: ed }) => {
      syncActive(ed)
      syncTableBar(ed)
    },
    onTransaction: ({ editor: ed }) => {
      syncActive(ed)
      syncTableBar(ed)
    },
    onUpdate: ({ editor: ed }) => {
      bodyField.value = ed.getHTML()
    },
  })

  bodyField.value = editor.getHTML()

  const syncTableBar = (ed) => {
    if (!tableBar) return
    const inTable = ed.isActive('table')
    tableBar.hidden = !inTable
    tableBar.querySelectorAll('[data-command]').forEach((btn) => {
      const cmd = btn.getAttribute('data-command')
      let enabled = inTable
      if (cmd === 'mergeCells') enabled = inTable && ed.can().mergeCells()
      if (cmd === 'splitCell') enabled = inTable && ed.can().splitCell()
      btn.disabled = !enabled
      btn.classList.toggle('is-disabled', !enabled)
    })
  }

  const syncActive = (ed) => {
    document.querySelectorAll('.docs-tool[data-command]').forEach((btn) => {
      const cmd = btn.getAttribute('data-command')
      let active = false
      if (cmd === 'bold') active = ed.isActive('bold')
      if (cmd === 'italic') active = ed.isActive('italic')
      if (cmd === 'underline') active = ed.isActive('underline')
      if (cmd === 'strike') active = ed.isActive('strike')
      if (cmd === 'bulletList') active = ed.isActive('bulletList')
      if (cmd === 'orderedList') active = ed.isActive('orderedList')
      if (cmd === 'blockquote') active = ed.isActive('blockquote')
      if (cmd === 'alignLeft') active = ed.isActive({ textAlign: 'left' })
      if (cmd === 'alignCenter') active = ed.isActive({ textAlign: 'center' })
      if (cmd === 'alignRight') active = ed.isActive({ textAlign: 'right' })
      if (cmd === 'toggleHeaderRow') active = ed.isActive('tableHeader')
      btn.classList.toggle('is-active', active)
    })

    const headingSelect = document.querySelector('.docs-select[data-command="heading"]')
    if (headingSelect) {
      if (ed.isActive('heading', { level: 1 })) headingSelect.value = '1'
      else if (ed.isActive('heading', { level: 2 })) headingSelect.value = '2'
      else if (ed.isActive('heading', { level: 3 })) headingSelect.value = '3'
      else headingSelect.value = 'paragraph'
    }
  }

  const closeTablePicker = () => {
    if (!tablePicker || !tablePickerBtn) return
    tablePicker.hidden = true
    tablePickerBtn.setAttribute('aria-expanded', 'false')
  }

  const openTablePicker = () => {
    if (!tablePicker || !tablePickerBtn) return
    tablePicker.hidden = false
    tablePickerBtn.setAttribute('aria-expanded', 'true')
    highlightPicker(3, 3)
  }

  const highlightPicker = (rows, cols) => {
    if (!tableGrid) return
    tableGrid.querySelectorAll('[data-r]').forEach((cell) => {
      const r = Number(cell.getAttribute('data-r'))
      const c = Number(cell.getAttribute('data-c'))
      cell.classList.toggle('is-on', r <= rows && c <= cols)
    })
    if (tablePickerLabel) tablePickerLabel.textContent = `${rows} × ${cols} table`
  }

  const buildTablePicker = () => {
    if (!tableGrid) return
    tableGrid.innerHTML = ''
    const max = 8
    for (let r = 1; r <= max; r += 1) {
      for (let c = 1; c <= max; c += 1) {
        const cell = document.createElement('button')
        cell.type = 'button'
        cell.className = 'docs-table-cell-pick'
        cell.setAttribute('data-r', String(r))
        cell.setAttribute('data-c', String(c))
        cell.setAttribute('aria-label', `${r} by ${c}`)
        cell.addEventListener('mouseenter', () => highlightPicker(r, c))
        cell.addEventListener('focus', () => highlightPicker(r, c))
        cell.addEventListener('click', (e) => {
          e.preventDefault()
          editor
            .chain()
            .focus()
            .insertTable({ rows: r, cols: c, withHeaderRow: true })
            .run()
          closeTablePicker()
        })
        tableGrid.appendChild(cell)
      }
    }
    highlightPicker(3, 3)
  }

  buildTablePicker()

  const run = (command) => {
    const chain = editor.chain().focus()
    switch (command) {
      case 'undo':
        chain.undo().run()
        break
      case 'redo':
        chain.redo().run()
        break
      case 'bold':
        chain.toggleBold().run()
        break
      case 'italic':
        chain.toggleItalic().run()
        break
      case 'underline':
        chain.toggleUnderline().run()
        break
      case 'strike':
        chain.toggleStrike().run()
        break
      case 'bulletList':
        chain.toggleBulletList().run()
        break
      case 'orderedList':
        chain.toggleOrderedList().run()
        break
      case 'blockquote':
        chain.toggleBlockquote().run()
        break
      case 'alignLeft':
        chain.setTextAlign('left').run()
        break
      case 'alignCenter':
        chain.setTextAlign('center').run()
        break
      case 'alignRight':
        chain.setTextAlign('right').run()
        break
      case 'clear':
        chain.clearNodes().unsetAllMarks().run()
        break
      case 'tablePicker':
        if (tablePicker?.hidden) openTablePicker()
        else closeTablePicker()
        break
      case 'addRowBefore':
        chain.addRowBefore().run()
        break
      case 'addRowAfter':
        chain.addRowAfter().run()
        break
      case 'addColumnBefore':
        chain.addColumnBefore().run()
        break
      case 'addColumnAfter':
        chain.addColumnAfter().run()
        break
      case 'deleteRow':
        chain.deleteRow().run()
        break
      case 'deleteColumn':
        chain.deleteColumn().run()
        break
      case 'mergeCells':
        chain.mergeCells().run()
        break
      case 'splitCell':
        chain.splitCell().run()
        break
      case 'toggleHeaderRow':
        chain.toggleHeaderRow().run()
        break
      case 'deleteTable':
        chain.deleteTable().run()
        break
      case 'link': {
        const prev = editor.getAttributes('link').href || ''
        const url = window.prompt('Link URL', prev)
        if (url === null) return
        if (url === '') {
          chain.extendMarkRange('link').unsetLink().run()
        } else {
          chain.extendMarkRange('link').setLink({ href: url }).run()
        }
        break
      }
      case 'image':
        imageInput?.click()
        break
      default:
        break
    }
  }

  document.querySelectorAll('.docs-tool[data-command]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault()
      run(btn.getAttribute('data-command'))
    })
  })

  document.querySelector('.docs-select[data-command="heading"]')?.addEventListener('change', (e) => {
    const value = e.target.value
    const chain = editor.chain().focus()
    if (value === 'paragraph') chain.setParagraph().run()
    else chain.toggleHeading({ level: Number(value) }).run()
  })

  document.addEventListener('click', (e) => {
    if (!tableInsert) return
    if (!tableInsert.contains(e.target)) closeTablePicker()
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeTablePicker()
  })

  imageInput?.addEventListener('change', async () => {
    const file = imageInput.files?.[0]
    if (!file) return
    const data = new FormData()
    data.append('image', file)
    data.append('_token', cfg.csrfToken || '')
    try {
      const res = await fetch(cfg.uploadUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      })
      const json = await res.json()
      if (!json.ok || !json.url) {
        window.alert(json.error || 'Image upload failed.')
        return
      }
      editor.chain().focus().setImage({ src: json.url }).run()
    } catch {
      window.alert('Image upload failed.')
    } finally {
      imageInput.value = ''
    }
  })

  form?.addEventListener('submit', () => {
    bodyField.value = editor.getHTML()
  })

  editor.view.dom.addEventListener('keydown', (e) => {
    const mod = e.metaKey || e.ctrlKey
    if (!mod) return
    const key = e.key.toLowerCase()
    if (key === 'b') {
      e.preventDefault()
      run('bold')
    } else if (key === 'i') {
      e.preventDefault()
      run('italic')
    } else if (key === 'u') {
      e.preventDefault()
      run('underline')
    } else if (key === 's' && e.shiftKey === false) {
      bodyField.value = editor.getHTML()
    }
  })

  syncActive(editor)
  syncTableBar(editor)
}

/* Featured image live preview */
;(() => {
  const root = document.querySelector('[data-featured-image]')
  if (!root) return
  const input = root.querySelector('[data-featured-input]')
  const preview = root.querySelector('[data-featured-preview]')
  const img = root.querySelector('[data-featured-img]')
  const empty = root.querySelector('[data-featured-empty]')
  const actions = root.querySelector('[data-featured-actions]')
  const removeBtn = root.querySelector('[data-featured-remove]')
  const removeFlag = root.querySelector('[data-featured-remove-flag]')
  const box = root.querySelector('.featured-image-box')
  let objectUrl = null

  const showPreview = (url) => {
    if (!img || !preview || !empty || !actions || !box) return
    img.src = url
    img.hidden = false
    preview.hidden = false
    empty.hidden = true
    actions.hidden = false
    box.classList.add('has-preview')
    if (removeFlag) removeFlag.value = '0'
  }

  const clearPreview = () => {
    if (!img || !preview || !empty || !actions || !box) return
    if (objectUrl) {
      URL.revokeObjectURL(objectUrl)
      objectUrl = null
    }
    img.removeAttribute('src')
    img.hidden = true
    preview.hidden = true
    empty.hidden = false
    actions.hidden = true
    box.classList.remove('has-preview')
    if (input) input.value = ''
    if (removeFlag) removeFlag.value = '1'
  }

  input?.addEventListener('change', () => {
    const file = input.files?.[0]
    if (!file) return
    if (objectUrl) URL.revokeObjectURL(objectUrl)
    objectUrl = URL.createObjectURL(file)
    showPreview(objectUrl)
  })

  removeBtn?.addEventListener('click', (e) => {
    e.preventDefault()
    clearPreview()
  })
})()
