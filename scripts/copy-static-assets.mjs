import { cp, mkdir } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const repositoryRoot = fileURLToPath(new URL('../', import.meta.url))
const appRoot = path.join(repositoryRoot, 'v1.23.0')
const outputRoot = path.join(repositoryRoot, 'public')

await mkdir(outputRoot, { recursive: true })
await Promise.all(['assets', 'vendors'].map((folder) =>
  cp(path.join(appRoot, folder), path.join(outputRoot, folder), { recursive: true }),
))

await mkdir(path.join(outputRoot, 'vendors', 'jquery'), { recursive: true })
await mkdir(path.join(outputRoot, 'vendors', 'summernote', 'font'), { recursive: true })
await cp(path.join(repositoryRoot, 'node_modules', 'jquery', 'dist', 'jquery.min.js'), path.join(outputRoot, 'vendors', 'jquery', 'jquery.min.js'))
await Promise.all(['summernote-lite.min.js', 'summernote-lite.min.css'].map((file) =>
  cp(path.join(repositoryRoot, 'node_modules', 'summernote', 'dist', file), path.join(outputRoot, 'vendors', 'summernote', file)),
))
await cp(path.join(repositoryRoot, 'node_modules', 'summernote', 'dist', 'font'), path.join(outputRoot, 'vendors', 'summernote', 'font'), { recursive: true })
