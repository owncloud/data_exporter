/**
 * Author Ilja Neumann <ineumann@owncloud.com>
 *
 * Copyright (c) 2019, ownCloud GmbH
 * License GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"os"
	"path"
	"path/filepath"
)

const Usage = `Usage: obj2localstore objectPath exportPath

  objectPath - Directory which contains files in urn:oid:$fileId form.
  exportPath - Directory which contains the export of one or multiple users`

type File struct {
	Id   int    `json:"id"`
	Type string `json:"type"`
	Path string `json:"path"`
}

func init() {
	args := os.Args[1:]
	if len(args) != 2 {
		fmt.Println(Usage)
		os.Exit(1)
	}
}

func main() {
	args := os.Args[1:]
	objectDir := args[0]
	exportPath := args[1]

	exportFiles, err := ioutil.ReadDir(exportPath)
	if err != nil {
		log.Fatal(err)
	}

	for _, f := range exportFiles {
		if !f.IsDir() {
			continue
		}

		metaDataPath := path.Join(exportPath, f.Name(), "files.jsonl")
		filePath := path.Join(exportPath, f.Name(), "files")
		if _, err := os.Stat(metaDataPath); err != nil {
			log.Println(err)
		} else {
			log.Printf("Creating userdir for %v", metaDataPath)
			buildUserDir(metaDataPath, filePath, objectDir)
		}
	}

}

func buildUserDir(metaDataPath string, filesPath string, objectDir string) {

	rollbackLog, err := os.OpenFile("./rollback.txt", os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
	if err != nil {
		log.Println(err)
	}

	defer rollbackLog.Close()

	forEachFile(metaDataPath, func(f *File) {
		fullPath := path.Join(filesPath, f.Path)
		if f.Type == "folder" {
			if err := os.MkdirAll(fullPath, 0777); err != nil {
				log.Fatal(err)
			}

			log.Printf("Created Directory: %v", fullPath)
			return
		}

		if f.Type == "file" {
			if _, err := os.Stat(fullPath); os.IsNotExist(err) {
				fileDir := filepath.Dir(fullPath)
				if err := os.MkdirAll(fileDir, 0777); err != nil {
					log.Fatal("Error creating missing directory for file", err)
				}
			}

			objectFilePath := path.Join(objectDir, fmt.Sprintf("urn:oid:%v", f.Id))
			if err := os.Rename(objectFilePath, fullPath); err != nil {
				log.Fatal(err)
			}

			log.Printf("Moved %v to %v", objectFilePath, fullPath)
			if _, err := rollbackLog.WriteString(fmt.Sprintf("mv %v %v\n", fullPath, objectFilePath)); err != nil {
				log.Fatal("Error writing rollback-file:", err)
			}
		}
	})
}

//Callback iterator for files.jsonl
func forEachFile(path string, fn func(metaData *File)) {
	filesJSONL, err := os.Open(path)
	if err != nil {
		log.Fatal(err)
	}

	defer filesJSONL.Close()
	jsonLines := bufio.NewScanner(filesJSONL)
	for jsonLines.Scan() {
		var f File
		if err := json.Unmarshal(jsonLines.Bytes(), &f); err != nil {
			log.Fatal(err)
		}

		fn(&f)
	}
}
