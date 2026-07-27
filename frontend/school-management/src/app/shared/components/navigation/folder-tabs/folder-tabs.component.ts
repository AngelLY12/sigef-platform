import { Component, EventEmitter, Input, Output, TemplateRef } from '@angular/core';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { NgTemplateOutlet } from '@angular/common';

@Component({
  selector: 'app-folder-tabs',
  standalone: true,
  imports: [NgTemplateOutlet],
  templateUrl: './folder-tabs.component.html',
  styleUrl: './folder-tabs.component.scss'
})
export class FolderTabsComponent {
  @Input() tabs: FolderTab[] = [];
  @Input() activeTab: string | null = null;
  @Input() actionsTemplate?: TemplateRef<unknown>;

  @Output() activeTabChange = new EventEmitter<string>();

  selectTab(tab: FolderTab) {
    if (tab.disabled || tab.id === this.activeTab) {
      return;
    }
    this.activeTab = tab.id;
    this.activeTabChange.emit(tab.id);
  }

}
