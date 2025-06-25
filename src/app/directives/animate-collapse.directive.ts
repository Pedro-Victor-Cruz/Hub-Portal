import {Directive, ElementRef, HostBinding, Input} from '@angular/core';

@Directive({
  standalone: true,
  selector: '[appAnimateCollapse]'
})
export class AnimateCollapseDirective {

  @Input() isExpanded: boolean = false;

  @HostBinding('class.collapsing') get collapsing() {
    return !this.isExpanded;
  }

  @HostBinding('style.height') get height() {
    return this.isExpanded ? this.getContentHeight() + 'px' : '0';
  }

  constructor(private el: ElementRef) {}

  private getContentHeight(): number {
    return this.el.nativeElement.scrollHeight;
  }

}
